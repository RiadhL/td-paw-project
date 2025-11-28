$(document).ready(function() {
    updateRowColors();
    updateMessages();

    $('#searchName').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('#summaryTable tbody tr').filter(function() {
            const lastName = $(this).find('.last-name').text().toLowerCase();
            const firstName = $(this).find('.first-name').text().toLowerCase();
            $(this).toggle(lastName.includes(searchTerm) || firstName.includes(searchTerm));
        });
    });

    $('#sortAbsences').click(function() {
        sortTable('absences', 'asc');
        $('#sortStatus').text('Currently sorted by absences (ascending)');
    });

    $('#sortParticipation').click(function() {
        sortTable('participation', 'desc');
        $('#sortStatus').text('Currently sorted by participation (descending)');
    });

    $('#highlightExcellent').click(function() {
        $('#summaryTable tbody tr').each(function() {
            const absences = parseInt($(this).data('absences')) || 0;
            if (absences < 3) {
                $(this).addClass('excellent-highlight');
                $(this).fadeOut(300).fadeIn(300).fadeOut(300).fadeIn(300);
            }
        });
    });

    $('#resetColors').click(function() {
        $('#summaryTable tbody tr').removeClass('excellent-highlight');
        updateRowColors();
    });

    $('#showReport').click(function() {
        const $reportSection = $('#reportSection');
        
        if ($reportSection.is(':visible')) {
            $reportSection.slideUp();
            return;
        }

        const rows = $('#summaryTable tbody tr');
        const totalStudents = rows.length;
        
        let totalAbsences = 0;
        let totalParticipations = 0;
        let studentsWithParticipation = 0;
        let totalSessions = 0;

        rows.each(function() {
            const absences = parseInt($(this).data('absences')) || 0;
            const participation = parseInt($(this).data('participation')) || 0;
            totalAbsences += absences;
            totalParticipations += participation;
            if (participation > 0) studentsWithParticipation++;
        });

        const sessionsPerStudent = $('.summary-table thead tr:first th').length - 5;
        totalSessions = totalStudents * sessionsPerStudent;
        const presentCount = totalSessions - totalAbsences;
        const avgAttendance = totalSessions > 0 ? Math.round((presentCount / totalSessions) * 100) : 0;

        $('#totalStudents').text(totalStudents);
        $('#avgAttendance').text(avgAttendance + '%');
        $('#studentsParticipated').text(studentsWithParticipation);

        $reportSection.slideDown();

        if (window.attendanceChart) {
            window.attendanceChart.destroy();
        }

        const ctx = document.getElementById('attendanceChart').getContext('2d');
        window.attendanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Present', 'Absent', 'Participated'],
                datasets: [{
                    label: 'Count',
                    data: [presentCount, totalAbsences, totalParticipations],
                    backgroundColor: ['#4CAF50', '#f44336', '#2196F3']
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
});

function updateRowColors() {
    $('#summaryTable tbody tr').each(function() {
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

function updateMessages() {
    $('#summaryTable tbody tr').each(function() {
        const absences = parseInt($(this).data('absences')) || 0;
        const participation = parseInt($(this).data('participation')) || 0;
        
        let attendanceMsg = '';
        let participationMsg = '';
        
        if (absences < 3) {
            attendanceMsg = 'Good attendance';
        } else if (absences <= 4) {
            attendanceMsg = 'Warning - attendance low';
        } else {
            attendanceMsg = 'Excluded - too many absences';
        }
        
        if (participation >= 4) {
            participationMsg = 'Excellent participation';
        } else if (participation >= 2) {
            participationMsg = 'Good participation';
        } else {
            participationMsg = 'You need to participate more';
        }
        
        $(this).find('.message-cell').text(attendanceMsg + ' - ' + participationMsg);
    });
}

function sortTable(criteria, order) {
    const $tbody = $('#summaryTable tbody');
    const rows = $tbody.find('tr').toArray();
    
    rows.sort(function(a, b) {
        let valA, valB;
        
        if (criteria === 'absences') {
            valA = parseInt($(a).data('absences')) || 0;
            valB = parseInt($(b).data('absences')) || 0;
        } else if (criteria === 'participation') {
            valA = parseInt($(a).data('participation')) || 0;
            valB = parseInt($(b).data('participation')) || 0;
        }
        
        if (order === 'asc') {
            return valA - valB;
        } else {
            return valB - valA;
        }
    });
    
    $tbody.empty();
    $.each(rows, function(index, row) {
        $tbody.append(row);
    });
    
    updateRowColors();
}
