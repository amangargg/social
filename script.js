document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    console.log("Form submitted");
    var username = document.getElementById('username').value;
    var password = document.getElementById('password').value;
    console.log("Username:", username);

    // Send request to login.php
    fetch('login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `username=${username}&password=${password}`
    })
    .then(response => {
        console.log("Response received", response);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(data => {
        console.log("Data received:", data);
        if (data === 'success') {
            window.location.href = 'welcome.php';
        } else {
            alert('Invalid username or password');
        }
    })
    .catch(error => {
        console.error('There has been a problem with your fetch operation:', error);
    });
});