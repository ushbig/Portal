document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('signupForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the form from submitting
        validateForm();
    });
});

function validateForm() {
    // Basic validation for demonstration
    var firstName = document.getElementById('firstName').value.trim();
    var lastName = document.getElementById('lastName').value.trim();
    var email = document.getElementById('email').value.trim();
    var password = document.getElementById('password').value;

    if (firstName === '' || lastName === '' || email === '' || password === '') {
        alert('Please fill in all fields.');
        return;
    }

    // You can add more complex validation logic here if needed

    // If validation passes, submit the form
    document.getElementById('signupForm').submit();
}
