import express from "express";
import {
    ApplicationStatus,
    MatchingStatsData,
    PayoutToken,
    ProjectApplication,
    Round,
} from "./types";
import { ethers } from "ethers";
import { ChainId, formatAmount, payoutTokens } from "./utils";
import {
    fetchPayoutTokenPrice,
    getProjectsApplications,
    getRoundById,
} from "./round";
import { Address } from "viem";

const app = express();

app.get("/", (req, res) => {
    res.send("An API for querying match pool funding");
});

app.get("/get-match-pool-amount", async (req, res) => {
    try {
        const chainId = Number(req.query.chainId);
        const roundId: Address = req.query.roundId as Address;
        const projectId: Address = req.query.projectId as Address;

        // // Validate inputs
        if (!chainId || !roundId || !projectId) {
            return res
                .status(400)
                .send("Missing chainId or roundId or projectId");
        }

        let roundData: Round | undefined = undefined,
            tokenAmount = 0,
            applications:
                | (ProjectApplication & { matchingData?: MatchingStatsData })[]
                | undefined = undefined;

        const { data } = await getRoundById(chainId, roundId);

        if (!data?.metadata?.quadraticFundingConfig?.matchingFundsAvailable)
            throw new Error("No round metadata");
        const matchingFundPayoutToken: PayoutToken = payoutTokens.filter(
            (t) => t.address.toLowerCase() == data?.token.toLowerCase(),
        )[0];
        tokenAmount = parseFloat(
            ethers.utils.formatUnits(
                data.matchAmount,
                matchingFundPayoutToken.decimal,
            ),
        );

        // get payout token price
        const signerOrProvider =
            chainId == ChainId.PGN
                ? new ethers.providers.JsonRpcProvider(
                      "https://rpc.publicgoods.network",
                      chainId,
                  )
                : chainId == ChainId.FANTOM_MAINNET_CHAIN_ID
                  ? new ethers.providers.JsonRpcProvider(
                        "https://rpcapi.fantom.network/",
                        chainId,
                    )
                  : new ethers.providers.InfuraProvider(
                        chainId,
                        process.env.NEXT_PUBLIC_INFURA_API_KEY,
                    );

        const price = await fetchPayoutTokenPrice(
            roundId,
            signerOrProvider,
            matchingFundPayoutToken,
        );
        const rate = price ? price : data.matchAmountUSD / tokenAmount;
        const matchingPoolUSD =
            data.metadata?.quadraticFundingConfig?.matchingFundsAvailable *
            rate;

        roundData = { ...data, matchingPoolUSD, rate, matchingFundPayoutToken };

        // applications data
        applications = await getProjectsApplications(roundId, chainId);
        if (!applications) throw new Error("No applications");

        let totalAmountUSD = 0;
        for (let application of applications) {
            if (
                application.projectId === projectId &&
                application.roundId === roundId
            ) {
                totalAmountUSD += application.amountUSD;
            }
        }

        res.json({ totalAmountUSD: totalAmountUSD });
    } catch (err) {
        console.error(err);
        res.status(500).send("Server error");
    }
});

app.listen(3000, () => {
    console.log("Server is running on port 3000");
});
