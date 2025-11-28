$(document).ready(function() {
    $('#studentForm').on('submit', function(e) {
        let isValid = true;
        
        $('.error-message').text('');
        
        const studentId = $('#student_id').val();
        if (!studentId || !/^\d+$/.test(studentId)) {
            $('#student_id-error').text('Student ID must contain only numbers');
            isValid = false;
        }
        
        const fullname = $('#fullname').val();
        if (!fullname || !/^[a-zA-Z\s]+$/.test(fullname)) {
            $('#fullname-error').text('Name must contain only letters');
            isValid = false;
        }
        
        const email = $('#email').val();
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            $('#email-error').text('Please enter a valid email address');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });

    $('#loginForm').on('submit', function(e) {
        let isValid = true;
        
        $('.error-message').text('');
        
        const username = $('#username').val();
        if (!username) {
            $('#username-error').text('Username is required');
            isValid = false;
        }
        
        const password = $('#password').val();
        if (!password) {
            $('#password-error').text('Password is required');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });

    $('#justificationForm').on('submit', function(e) {
        let isValid = true;
        
        $('.error-message').text('');
        
        const reason = $('#reason').val();
        if (!reason || reason.length < 10) {
            $('#reason-error').text('Please provide a detailed reason (at least 10 characters)');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
});
