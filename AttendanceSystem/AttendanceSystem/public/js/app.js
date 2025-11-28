$(document).ready(function() {
    $('#searchStudent').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('#attendanceTable tbody tr, #studentsTable tbody tr').each(function() {
            const name = $(this).data('student-name') || $(this).find('td').text().toLowerCase();
            $(this).toggle(name.includes(searchTerm));
        });
    });

    $('#markAllPresent').click(function() {
        $('input[type="radio"][value="present"]').prop('checked', true);
    });

    $('#markAllAbsent').click(function() {
        $('input[type="radio"][value="absent"]').prop('checked', true);
    });

    $('.attendance-table tbody tr, .summary-table tbody tr').hover(
        function() {
            $(this).addClass('row-hover').css('background-color', '#e3f2fd');
        },
        function() {
            $(this).removeClass('row-hover');
            updateRowColors();
        }
    );

    $('.attendance-table tbody tr, .summary-table tbody tr').click(function() {
        const firstName = $(this).find('.first-name').text() || $(this).find('td:eq(1)').text();
        const lastName = $(this).find('.last-name').text() || $(this).find('td:eq(0)').text();
        const absences = $(this).data('absences') || $(this).find('.absences-count').text();
        
        alert('Student: ' + lastName + ' ' + firstName + '\nAbsences: ' + absences);
    });
});

function updateRowColors() {
    $('.summary-table tbody tr').each(function() {
        const absences = parseInt($(this).data('absences')) || 0;
        
        $(this).removeClass('row-green row-yellow row-red');
        
        if (absences < 3) {
            $(this).addClass('row-green');
        } else if (absences <= 4) {
            $(this).addClass('row-yellow');
        } else {
            $(this).addClass('row-red');
        }
    });
}
