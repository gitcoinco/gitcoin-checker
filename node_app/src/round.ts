/* eslint-disable @typescript-eslint/no-explicit-any */
import { fetchFromIPFS, findRoundById, formatCurrency } from "./utils";
import { Address, getAddress } from "viem";
import dayjs from "dayjs";
import LocalizedFormat from "dayjs/plugin/localizedFormat";
import redstone from "redstone-api";
import {
    ApplicationStatus,
    Eligibility,
    MatchingStatsData,
    MetadataPointer,
    PayoutToken,
    ProjectApplication,
    Round,
} from "./types";
import { BigNumber, ethers } from "ethers";
import roundImplementationAbi from "./abi/roundImplementation";
import merklePayoutStrategyImplementationAbi from "./abi/merklePayoutStrategyImplementation";

dayjs.extend(LocalizedFormat);

/**
 * Shape of subgraph response
 */
export interface GetRoundByIdResult {
    data: {
        rounds: RoundResult[];
    };
}

/**
 * Shape of subgraph response of Round
 */
export interface RoundResult {
    id: string;
    program: {
        id: string;
    };
    roundMetaPtr: MetadataPointer;
    applicationMetaPtr: MetadataPointer;
    applicationsStartTime: string;
    applicationsEndTime: string;
    donationsStartTime: string;
    donationsEndTime: string;
    matchTokenAddress: string;
    votingStrategy: string;
    projectsMetaPtr?: MetadataPointer | null;
}

export interface ProjectVote {
    id: string;
    transaction: string;
    blockNumber: number;
    projectId: string;
    applicationId: string;
    roundId: string;
    voter: Address;
    grantAddress: Address;
    token: string;
    amount: string;
    totalAmountDonatedInUsd: number;
    amountRoundToken: string;
}
/**
 * Shape of IPFS content of Round RoundMetaPtr
 */
export type RoundMetadata = {
    name: string;
    roundType: string;
    eligibility: Eligibility;
    programContractAddress: string;
};

export type RoundProject = {
    id: string;
    status: ApplicationStatus;
    payoutAddress: string;
};

export const getRoundById = async (
    chainId: number,
    roundId: string,
): Promise<{
    data: Round | undefined;
    success: boolean;
    error: string;
    allRounds: Round[] | undefined;
}> => {
    const allRounds = await getRoundsByChainId(chainId);
    if (!allRounds.success)
        return {
            success: false,
            error: allRounds.error,
            data: undefined,
            allRounds: undefined,
        };
    const round = findRoundById(allRounds.data || [], roundId);
    return {
        success: true,
        error: "",
        data: round,
        allRounds: allRounds.data,
    };
};

async function fetchWithTimeout(url: string, options = {}) {
    const timeout = 5000;

    const controller = new AbortController();
    const id = setTimeout(() => controller.abort(), timeout);

    const response = await fetch(url, {
        ...options,
        signal: controller.signal,
    });
    clearTimeout(id);

    return response;
}

export const getRoundsByChainId = async (
    chainId: number,
): Promise<{ data: Round[] | undefined; success: boolean; error: string }> => {
    try {
        const resp = await fetchWithTimeout(
            `https://indexer-production.fly.dev/data/${chainId}/rounds.json`,
            { next: { revalidate: 3600 } },
        );
        const data = (await resp.json()) as Round[];
        const filteredData = data?.filter(
            (round) =>
                !!round.roundMetadata?.name &&
                !!round.roundMetadata.quadraticFundingConfig
                    ?.matchingFundsAvailable &&
                !!round.totalDonationsCount &&
                !round.roundMetadata?.name.toLowerCase().includes("test") &&
                round.totalAmountDonatedInUsd > 50,
        );

        return {
            data: filteredData,
            success: true,
            error: "",
        };
    } catch (err) {
        return { data: undefined, success: false, error: err as string };
    }
};

export function convertStatus(status: string | number) {
    switch (status) {
        case 0:
            return "PENDING";
        case 1:
            return "APPROVED";
        case 2:
            return "REJECTED";
        case 3:
            return "CANCELLED";
        default:
            return "PENDING";
    }
}

export const getProjectsApplications = async (
    roundId: Address,
    chainId: number,
) => {
    try {
        const resp = await fetchWithTimeout(
            `https://indexer-production.fly.dev/data/${chainId}/rounds/${getAddress(
                roundId,
            )}/applications.json`,
            { next: { revalidate: 3600 } },
        );
        const data = (await resp.json()) as ProjectApplication[];

        const approvedData = data.filter(
            (ap) => ap.status == ApplicationStatus.APPROVED,
        );

        return approvedData;
    } catch (err) {
        console.log(err);
    }
};

export async function fetchPayoutTokenPrice(
    roundId: string | undefined,
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    signerOrProvider: any,
    token: PayoutToken,
) {
    if (!roundId) {
        throw new Error("Round ID is required");
    }
    const roundImplementation = new ethers.Contract(
        roundId,
        roundImplementationAbi,
        signerOrProvider,
    );
    const payoutStrategyAddress = await roundImplementation.payoutStrategy();
    const payoutStrategy = new ethers.Contract(
        payoutStrategyAddress,
        merklePayoutStrategyImplementationAbi,
        signerOrProvider,
    );

    const fundsDistributed =
        await payoutStrategy.queryFilter("FundsDistributed");

    if (fundsDistributed?.length) {
        const payoutTimestamp = (await fundsDistributed[0].getBlock())
            .timestamp;

        const payoutDate = dayjs.unix(Number(payoutTimestamp)).toString();
        const price = await redstone.getHistoricalPrice(
            token.redstoneTokenId || token.name,
            {
                date: payoutDate,
            },
        );
        return price.value;
    }
    return;
}
//  Fetch finalized matching distribution
export async function fetchMatchingDistribution(
    roundId: string | undefined,
    signerOrProvider: any,
    token: PayoutToken,
    roundMatchingPoolUSD: number,
) {
    try {
        if (!roundId) {
            throw new Error("Round ID is required");
        }
        let matchingDistribution: MatchingStatsData[] = [];
        const roundImplementation = new ethers.Contract(
            roundId,
            roundImplementationAbi,
            signerOrProvider,
        );
        const payoutStrategyAddress =
            await roundImplementation.payoutStrategy();
        const payoutStrategy = new ethers.Contract(
            payoutStrategyAddress,
            merklePayoutStrategyImplementationAbi,
            signerOrProvider,
        );
        const distributionMetaPtrRes =
            await payoutStrategy.distributionMetaPtr();
        const distributionMetaPtr = distributionMetaPtrRes.pointer;
        if (distributionMetaPtr !== "") {
            // fetch distribution from IPFS
            const matchingDistributionRes =
                await fetchFromIPFS(distributionMetaPtr);
            matchingDistribution = matchingDistributionRes.matchingDistribution;
            // parse matchAmountInToken to a valid BigNumber + add matchAmount
            matchingDistribution = matchingDistribution.map((distribution) => {
                const x = BigNumber.from(
                    (distribution.matchAmountInToken as any).hex,
                );
                distribution.matchAmountInToken = x;
                const z = formatCurrency(x, token.decimal);
                return {
                    ...distribution,
                    matchAmount: Number(z || 0),
                    matchAmountUSD:
                        distribution.matchPoolPercentage * roundMatchingPoolUSD,
                };
            });
        }
        return matchingDistribution;
    } catch (err) {
        console.log(err);
    }
}
