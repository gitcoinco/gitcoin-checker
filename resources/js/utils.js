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
    if (!url || url.length < limit) {
        return url;
    }
    return url.slice(0, limit) + separator + url.slice(-limit);
}
