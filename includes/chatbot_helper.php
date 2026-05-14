<?php
/**
 * Chatbot Helper - Defines FAQ data and Navigation Keyword mappings
 */

function getChatbotData() {
    return [
        'faqs' => [
            "How do I view my results?" => "You can view your results by going to the 'My Results' or 'Marks' section in your dashboard.",
            "How do I submit assignment?" => "Go to the 'Assignment' section, select your pending task, and upload your file (Max 50MB).",
            "How do I view timetable?" => "Click on the 'Timetable' link in your navigation sidebar to see your weekly schedule.",
            "How do I change my password?" => "You can change your password in the 'Account' or 'Profile' settings page.",
            "How do I contact my teacher?" => "You can find teacher contact details in your Course information or send a request via the Admin office.",
            "When is my next exam?" => "Check the 'Exams' section for upcoming test dates and locations."
        ],
        'navigation' => [
            'timetable' => ['text' => 'View Timetable', 'url' => 'view_timetable.php'],
            'results' => ['text' => 'View Results', 'url' => 'view_marks.php'],
            'marks' => ['text' => 'Check Marks', 'url' => 'view_marks.php'],
            'assignment' => ['text' => 'Assignment Hub', 'url' => 'student_assignments.php'],
            'notice' => ['text' => 'Read Notices', 'url' => 'view_notice.php'],
            'profile' => ['text' => 'Manage Profile', 'url' => 'profile.php'],
            'password' => ['text' => 'Security Settings', 'url' => 'profile.php'],
            'logout' => ['text' => 'Exit System', 'url' => 'logout.php']
        ]
    ];
}
?>
