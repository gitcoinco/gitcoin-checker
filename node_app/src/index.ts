import express from "express";
import {
    MatchingStatsData,
    PayoutToken,
    ProjectApplication,
    Round,
} from "./types";
import { ethers } from "ethers";
import { ChainId, payoutTokens } from "./utils";
import {
    fetchMatchingDistribution,
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

        // applications data from indexer
        const allApplications = await getProjectsApplications(roundId, chainId);
        if (!allApplications) throw new Error("No applications");

        // matching data
        const matchingData = await fetchMatchingDistribution(
            roundId,
            signerOrProvider,
            roundData.matchingFundPayoutToken,
            roundData.matchingPoolUSD,
        );

        // add .matchingData to applications
        applications = allApplications?.map((app) => {
            const projectMatchingData = matchingData?.find(
                (data) => data.projectId == app.projectId,
            );
            return {
                ...app,
                matchingData: projectMatchingData,
            };
        });

        // find the project
        const project = applications?.find(
            (application) =>
                application.projectId == projectId &&
                application.roundId === roundId,
        );
        if (!project) throw new Error("Project not found");
        if (!project.matchingData?.matchAmountUSD)
            throw new Error("No matching data for this project");

        // total amount = crowdfunded USD + matched USD
        const totalAmountUSD =
            project.amountUSD + project.matchingData.matchAmountUSD;

        res.json({
            donorAmountUSD: project.amountUSD,
            matchAmountUSD: project.matchingData.matchAmountUSD,
        });
    } catch (err) {
        console.error(err);
        res.status(500).send("Server error");
    }
});

app.listen(3000, () => {
    console.log("Server is running on port 3000");
});
