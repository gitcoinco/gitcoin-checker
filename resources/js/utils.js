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
