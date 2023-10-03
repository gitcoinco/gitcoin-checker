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
