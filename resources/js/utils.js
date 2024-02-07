// Take an ethereum address and shorten it
export function shortenAddress(
    address,
    preCharacters = 5,
    postCharacters = 5,
    separator = "..."
) {
    if (!address || address.length < preCharacters + postCharacters) {
        console.error("Invalid address");
        return address;
    }
    return (
        address.slice(0, preCharacters) +
        separator +
        address.slice(-postCharacters)
    );
}

export function copyToClipboard(text) {
    const textarea = document.createElement("textarea");
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand("copy");
    document.body.removeChild(textarea);
}

export function formatDate(input) {
    // Create a new Date object from the input string
    const date = new Date(input);

    // Array of month names
    const monthNames = [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
    ];

    // Extract day, month, and year from the Date object
    const day = date.getDate();
    const month = monthNames[date.getMonth()];
    const year = date.getFullYear();

    // Return the formatted date
    return `${day} ${month} ${year}`;
}

// Takes the GPT results and calculates the average score
export function scoreTotal(results) {
    if (results && results.length > 0) {
        let resultsData = results[0].results_data;

        let total = 0;

        // Try to parse resultsData into a json object
        try {
            resultsData = JSON.parse(resultsData);

            // Check if resultsData is an array and has items
            if (!Array.isArray(resultsData) || resultsData.length === 0) {
                return null;
            }
        } catch (error) {
            return resultsData;
        }

        // iterate over each result
        let counter = 0;
        for (let result of resultsData) {
            // Check if result has a score property and it's a number
            if (result && typeof result.score === "number") {
                // add the score to the total
                total += result.score;
                counter++;
            }
        }

        // Check if counter is not zero to avoid division by zero
        if (counter === 0) {
            return null;
        }

        total = total / counter;
        // set total to a max of 1 decimal
        total = total.toFixed(1);
        return total + "%";
    } else {
        return null;
    }
}

export function shortenURL(url, limit, separator = "...") {
    if (!url || url.length <= limit) {
        return url;
    }
    return url.slice(0, limit) + separator;
}

export function applicationStatusIcon(status) {
    status = status.toLowerCase();

    switch (status) {
        case "pending":
            return '<i class="fa fa-clock-o text-yellow-500" title="Pending"></i>';
        case "approved":
            return '<i class="fa fa-check-circle-o text-green-500" title="Approved"></i>';
        case "rejected":
            return '<i class="fa fa-times-circle-o text-red-500" title="Rejected"></i>';
        default:
            return '<i class="fa fa-exclamation-circle-o text-gray-500"></i>';
    }
}

export function showDateInShortFormat(date, includeHours = false) {
    let ret = new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });

    if (includeHours) {
        ret +=
            " @" +
            new Date(date).toLocaleTimeString("en-US", {
                hour: "numeric",
                minute: "numeric",
            });
    }

    return ret;
}

// Return the first name and initial of the last name
export function getShortenedName(name) {
    if (name.includes(" ")) {
        let splitName = name.split(" ");
        name = `${splitName[0]} ${splitName[1].charAt(0)}`;
    }

    return name;
}

// Return the probability of two strings matching
export function matchProbability(str1, str2) {
    let words1 = new Set(str1.split(/\s+/));
    let words2 = new Set(str2.split(/\s+/));
    let intersection = new Set([...words1].filter((word) => words2.has(word)));
    return intersection.size / new Set([...words1, ...words2]).size;
}

export function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}
