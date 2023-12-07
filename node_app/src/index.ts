import express from "express";
const app = express();

app.get("/", (req, res) => {
    res.send("An API for querying match pool funding");
});

// Pass in the application address as a query parameter and return the match pool amount
app.get("/get-match-pool-amount-from-application-address", (req, res) => {
    const applicationAddress = req.query.applicationAddress;
    console.log("applicationAddress: ", applicationAddress);
    res.send("Match pool amount for application " + applicationAddress);
});

app.listen(3000, () => {
    console.log("Server is running on port 3000");
});
