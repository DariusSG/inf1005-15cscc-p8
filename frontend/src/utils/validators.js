function isSITEmail(email) {
    return email.toLowerCase().endsWith('@sit.singaporetech.edu.sg');
}

function isValidPassword(pw) {
    return pw.length >= 6;
}

function passwordsMatch(pw, cpw) {
    return pw === cpw;
}
