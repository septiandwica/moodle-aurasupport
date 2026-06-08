<?php
/**
 * Import PRESOLA Knowledge Base Articles into AuraSupport
 *
 * This script bulk inserts highly tailored PJJ articles into the local_aurasupport_kb table.
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$admin = get_admin();
if (!$admin) {
    cli_error("Could not find a valid admin user to assign as the author.");
}

$time = time();

$articles = [
    // 1. Virtual Classrooms & Communication
    [
        'title' => '[Teacher] Setting up Live Sessions (Zoom / BigBlueButton)',
        'content' => '<h3>Welcome to the PRESOLA Helpdesk</h3>
<p>As a PRESOLA teacher, hosting live sessions is crucial for Distance Learning (PJJ). Moodle integrates seamlessly with platforms like Zoom and BigBlueButton.</p>
<h4>How to Create a Live Session:</h4>
<ol>
<li>Go to your Course Dashboard on the PRESOLA portal.</li>
<li>Click <strong>Turn editing on</strong> in the top right corner.</li>
<li>Navigate to the specific week or topic and click <strong>Add an activity or resource</strong>.</li>
<li>Select <strong>BigBlueButton</strong> (or Zoom, if configured).</li>
<li>Enter the Virtual Room Name (e.g., "Week 1: Introduction to Economics").</li>
<li>Under <strong>Schedule for session</strong>, set the open and close times.</li>
<li>Click <strong>Save and return to course</strong>.</li>
</ol>
<p><em>Tip: Always remind your students to check their Moodle Dashboard timeline for upcoming live sessions.</em></p>',
    ],
    [
        'title' => '[Student] How to Join Live Sessions and View Recordings',
        'content' => '<h3>Welcome to the PRESOLA Helpdesk</h3>
<p>If you have an upcoming virtual class, you can join it directly from your PRESOLA Moodle Dashboard without needing external links.</p>
<h4>How to Join a Live Class:</h4>
<ol>
<li>Log in to your PRESOLA account and open your <strong>Course</strong>.</li>
<li>Look for the activity marked with a blue "b" icon (BigBlueButton) or a Zoom icon.</li>
<li>Click the activity name.</li>
<li>When the session is open, click the <strong>Join Session</strong> button.</li>
</ol>
<h4>How to View Past Recordings:</h4>
<p>If you missed a class, go back to the exact same activity link. Below the "Join" button area, you will find a list titled <strong>Recordings</strong>. Click "Presentation" or "Video" to watch the replay.</p>',
    ],
    [
        'title' => '[Teacher] Moderating Asynchronous Discussion Forums',
        'content' => '<h3>Welcome to the PRESOLA Helpdesk</h3>
<p>For distance learning (PJJ) at PRESOLA, asynchronous forums are the primary way to measure student participation outside of live classes.</p>
<h4>Setting Up a Forum for Grading:</h4>
<ol>
<li>Turn editing on and click <strong>Add an activity or resource</strong> > <strong>Forum</strong>.</li>
<li>Name it "Discussion Forum: Week [X]".</li>
<li>Change the <strong>Forum type</strong> to "Standard forum for general use" or "Q and A forum" (forces students to post before seeing others\' replies).</li>
<li>Expand the <strong>Whole forum grading</strong> section, choose <em>Point</em> and set Maximum grade to 100.</li>
</ol>
<h4>Tracking Participation:</h4>
<p>You can use the <strong>Grade users</strong> button inside the forum to read a specific student\'s posts and instantly assign a grade that syncs to the Moodle Gradebook.</p>',
    ],

    // 2. Course Content & Progress Tracking
    [
        'title' => '[Teacher] Configuring Activity Completion & Restrict Access',
        'content' => '<h3>Welcome to the PRESOLA Helpdesk</h3>
<p>To ensure students in the PJJ program study the materials sequentially, PRESOLA recommends using <strong>Restrict Access</strong> and <strong>Activity Completion</strong>.</p>
<h4>How to Force Sequential Learning:</h4>
<ol>
<li>First, edit your "Material A" (e.g., a PDF or Video).</li>
<li>Go to <strong>Activity completion</strong> -> Choose <em>"Show activity as complete when conditions are met"</em> and check <em>"Student must view this activity to complete it"</em>. Save.</li>
<li>Next, edit "Quiz B".</li>
<li>Go to <strong>Restrict access</strong> -> Click <strong>Add restriction</strong> -> Select <strong>Activity completion</strong>.</li>
<li>Set it so that the student must mark "Material A" as complete before they can access "Quiz B".</li>
<li>Save. Now "Quiz B" will appear locked on the student\'s dashboard until they finish the prerequisite.</li>
</ol>',
    ],
    [
        'title' => '[Student] How to Track Your Own Course Progress',
        'content' => '<h3>Welcome to the PRESOLA Helpdesk</h3>
<p>In the PRESOLA Distance Learning environment, it is your responsibility to ensure you have completed all weekly tasks.</p>
<h4>Using the Completion Checkboxes:</h4>
<p>On the right side of most activities/resources (PDFs, Videos, Assignments) on your course page, you will see a small button.</p>
<ul>
<li><strong>Solid border:</strong> You can click it manually to mark it as done.</li>
<li><strong>Dotted border:</strong> The system will automatically mark it as "Done" once you meet the requirement (e.g., receiving a grade, or opening a file).</li>
</ul>
<h4>Checking Your Overall Progress:</h4>
<p>Go to your PRESOLA <strong>Dashboard</strong>. Under the "Course Overview" block, look at the progress bar percentage beneath your course name. Ensure it reaches 100% by the end of the semester.</p>',
    ],
    [
        'title' => '[Teacher] Importing Interactive Content (H5P/SCORM)',
        'content' => '<h3>Welcome to the PRESOLA Helpdesk</h3>
<p>To make distance learning at PRESOLA more engaging, you can embed interactive videos or presentations using H5P or SCORM packages.</p>
<h4>Uploading H5P Content:</h4>
<ol>
<li>Go to your course and open the <strong>Content bank</strong> from the left navigation menu.</li>
<li>Click <strong>Upload</strong> and select your `.h5p` file.</li>
<li>Return to your course homepage, Turn editing on, and click <strong>Add an activity or resource</strong>.</li>
<li>Select <strong>H5P</strong>. In the "Package file" area, click the file picker, choose "Content bank", and select the file you just uploaded.</li>
<li>Set the <strong>Grade</strong> settings if you want the interactive questions to automatically sync with your Moodle Gradebook.</li>
</ol>',
    ],

    // 3. Remote Assignments & Assessments
    [
        'title' => '[Student] Submitting Video or Large File Assignments',
        'content' => '<h3>Welcome to the PRESOLA Helpdesk</h3>
<p>Moodle has a strict maximum file upload limit. If your assignment is a large video presentation or a heavy project file, <strong>do not upload the file directly to Moodle</strong>.</p>
<h4>How to Submit via Cloud Link:</h4>
<ol>
<li>Upload your video/file to your Google Drive or Microsoft OneDrive.</li>
<li>Right-click the file and select <strong>Share</strong>.</li>
<li>Change the privacy settings to <em>"Anyone with the link can view"</em>.</li>
<li>Copy the link.</li>
<li>Go to your PRESOLA Moodle Course -> Click the <strong>Assignment</strong>.</li>
<li>Click <strong>Add submission</strong>.</li>
<li>If there is an <strong>Online text</strong> box, paste your link there. If there is only a File submission area, paste your link into a Word document or Notepad file, save it, and upload that small document.</li>
</ol>',
    ],
    [
        'title' => '[Teacher] Setting up Advanced Grading (Rubrics & Guides)',
        'content' => '<h3>Welcome to the PRESOLA Helpdesk</h3>
<p>For subjective assignments (like essays or video presentations), PRESOLA encourages the use of Moodle Rubrics to ensure transparent and standardized grading.</p>
<h4>Creating a Rubric:</h4>
<ol>
<li>Create an <strong>Assignment</strong> activity and scroll down to the <strong>Grade</strong> section.</li>
<li>Change the <strong>Grading method</strong> from "Simple direct grading" to <strong>Rubric</strong>. Save and display.</li>
<li>You will be prompted to "Define new grading form from scratch".</li>
<li>Give your rubric a name, then add your Criteria (e.g., "Clarity", "Formatting") and Levels (e.g., 0 points, 5 points, 10 points).</li>
<li>Click <strong>Save rubric and make it ready</strong>.</li>
</ol>
<p>When you click "Grade" on a student\'s submission, you will now see clickable boxes for each criteria, instantly calculating their final score.</p>',
    ],
    [
        'title' => '[Teacher] Exporting the Gradebook for SIAKAD Integration',
        'content' => '<h3>Welcome to the PRESOLA Helpdesk</h3>
<p>At the end of the semester, you must export your Moodle grades to report them to the academic administration (SIAKAD).</p>
<h4>How to Download Grades:</h4>
<ol>
<li>Go to your Course and click <strong>Grades</strong> from the navigation menu.</li>
<li>Click the <strong>Export</strong> tab.</li>
<li>Select <strong>Excel spreadsheet</strong>.</li>
<li>You will see a list of all activities. Uncheck any activities that do not count towards the final grade (e.g., practice quizzes).</li>
<li>Scroll down and click <strong>Download</strong>.</li>
</ol>
<p>You can use this Excel file to map the "Course total" column to your official PRESOLA grading sheets.</p>',
    ],

    // 4. NetraGo Proctoring (Online Exams)
    [
        'title' => '[Teacher] Enabling NetraGo Proctoring for Midterms & Finals',
        'content' => '<h3>Welcome to the PRESOLA Helpdesk</h3>
<p>NetraGo is PRESOLA\'s Advanced AI Proctoring System integrated directly into Moodle to secure remote examinations.</p>
<h4>How to Secure Your Quiz:</h4>
<ol>
<li>Create your <strong>Quiz</strong> as usual with your questions.</li>
<li>In the Quiz settings, go to the <strong>Extra restrictions on attempts</strong> section.</li>
<li>Set "Require browser security" to <em>None</em> (NetraGo handles this natively).</li>
<li>Scroll down to the <strong>NetraGo Proctoring Settings</strong> block (if available, or access via the NetraGo block).</li>
<li>Check the options you want: <em>Require Camera</em>, <em>Require Fullscreen</em>, <em>Disable Copy-Paste</em>, and <em>Disable Focus Loss</em>.</li>
<li>Set the <strong>Max Strikes</strong> (e.g., 3). If a student violates the rules 3 times, their attempt will be forcefully terminated.</li>
<li>Save. Students will now go through the NetraGo Preflight check before they can see the "Attempt Quiz" button.</li>
</ol>',
    ],
    [
        'title' => '[Teacher] Interpreting NetraGo Violation Reports',
        'content' => '<h3>Welcome to the PRESOLA Helpdesk</h3>
<p>Once students complete a NetraGo-proctored exam, you must review the reports to finalize their grades.</p>
<h4>Understanding the Report Dashboard:</h4>
<p>Navigate to the NetraGo Report block. You will see a list of students with a <strong>Trust Score</strong> (High, Moderate, Low).</p>
<p>Click "View report" to see the timeline of events. Look for the following <strong>Violation Codes</strong>:</p>
<ul>
<li><code>tab_switch_violation</code>: The student opened another browser tab or application (e.g., looking at notes).</li>
<li><code>gaze_down_violation</code>: The AI detected the student continuously looking down, possibly at a mobile phone hidden on their lap.</li>
<li><code>gaze_side_violation</code>: The student was looking far off-screen, possibly reading from a second monitor.</li>
<li><code>audio_noise_violation</code>: Sustained talking or loud background noise was detected.</li>
</ul>
<p>Use your discretion when reviewing the captured snapshot images before penalizing the student.</p>',
    ],
    [
        'title' => '[Student] Preparing for a NetraGo Proctored Exam (Preflight)',
        'content' => '<h3>Welcome to the PRESOLA Helpdesk</h3>
<p>Your upcoming Midterm/Final exam is secured by the NetraGo AI Proctoring system. To avoid technical issues or automatic exam termination, please read this carefully.</p>
<h4>Technical Checklist Before Starting:</h4>
<ol>
<li><strong>Browser:</strong> You MUST use Google Chrome or Microsoft Edge. Safari and Firefox are not fully supported and may cause false violations.</li>
<li><strong>Displays:</strong> Disconnect any external monitors (HDMI/DisplayPort). NetraGo prohibits dual-monitor setups.</li>
<li><strong>Camera:</strong> Virtual cameras (OBS, ManyCam) are banned. Ensure your physical webcam is connected.</li>
<li><strong>Permissions:</strong> When you click the Quiz, your browser will show a popup asking for Camera/Microphone access. Click <strong>Allow</strong>. If you accidentally block it, click the lock icon next to the URL bar and reset the permissions.</li>
<li><strong>Screen Sharing:</strong> You will be asked to share your screen. You MUST select the <strong>Entire Screen</strong> tab. Sharing only a window or Chrome tab will be rejected.</li>
</ol>
<p><em>Warning: Do not open other tabs or look away from the screen for extended periods during the exam, or your attempt will be forcefully terminated.</em></p>',
    ],

    // 5. Quizzes & Assessments (Student Guide)
    [
        'title' => '[Student] How to Take and Submit a Moodle Quiz',
        'content' => '<h3>Welcome to the PRESOLA Helpdesk</h3>
<p>Taking quizzes in PRESOLA requires a stable connection. Follow these steps to ensure your answers are recorded safely.</p>
<h4>Attempting the Quiz:</h4>
<ol>
<li>Click on the Quiz activity in your course.</li>
<li>Click <strong>Attempt quiz now</strong>. A timer will appear if the teacher has set a time limit.</li>
<li>Answer the questions. Moodle automatically saves your answers every time you change pages.</li>
<li>To manually force a save, you can click the "Next page" or "Previous page" buttons.</li>
<li>When you reach the end, click <strong>Finish attempt...</strong></li>
<li>Review your summary. If you are ready, click <strong>Submit all and finish</strong>. You MUST confirm the final popup for your quiz to be submitted.</li>
</ol>',
    ],
    [
        'title' => '[Student] Understanding Quiz Review and Feedback',
        'content' => '<h3>Welcome to the PRESOLA Helpdesk</h3>
<p>After submitting a quiz on PRESOLA, you might not see your grade or the correct answers immediately.</p>
<h4>Why can\'t I see the correct answers?</h4>
<p>Teachers often configure the <strong>Review options</strong> to hide the correct answers until <em>After the quiz is closed</em> for everyone. This prevents answer sharing during the exam period.</p>
<h4>How to check your feedback later:</h4>
<ol>
<li>Return to the Quiz activity page once the deadline has passed.</li>
<li>Click <strong>Review</strong> next to your previous attempt.</li>
<li>Look for green checkmarks (correct) or red crosses (incorrect). Read the specific feedback box below each question if the teacher provided an explanation.</li>
</ol>',
    ],

    // 6. Group Work & Collaboration
    [
        'title' => '[Teacher] Setting up Groups and Groupings for Assignments',
        'content' => '<h3>Welcome to the PRESOLA Helpdesk</h3>
<p>If you have group projects in your PJJ course, you should set up Groups in Moodle so that grading one student automatically applies the grade to the entire group.</p>
<h4>Step 1: Creating Groups</h4>
<ol>
<li>Go to <strong>Participants</strong> > Click the gear icon > <strong>Groups</strong>.</li>
<li>Click <strong>Auto-create groups</strong> or <strong>Create group</strong> manually to assign students.</li>
</ol>
<h4>Step 2: Configuring the Assignment</h4>
<ol>
<li>Edit your Assignment activity and scroll down to <strong>Group submission settings</strong>.</li>
<li>Set <em>Students submit in groups</em> to <strong>Yes</strong>.</li>
<li>Set <em>Require all group members submit</em> to <strong>No</strong> (this ensures if one student uploads the file, the whole group gets it).</li>
<li>Save and display.</li>
</ol>',
    ],
    [
        'title' => '[Student] How to Submit a Group Assignment',
        'content' => '<h3>Welcome to the PRESOLA Helpdesk</h3>
<p>If your teacher has assigned a group project on PRESOLA, the submission process is slightly different to ensure your whole team gets the grade.</p>
<h4>Who should submit?</h4>
<p>Only <strong>ONE</strong> member of your group needs to upload the final file or cloud link.</p>
<h4>How it works:</h4>
<ol>
<li>The designated group leader clicks <strong>Add submission</strong> and uploads the file.</li>
<li>Once submitted, the file will automatically appear on the dashboards of all other group members.</li>
<li>When the teacher grades the submission, the grade and feedback are instantly copied to every member of your group.</li>
</ol>
<p><em>Note: If another member edits the submission before the deadline, it will overwrite the previous file for the whole group.</em></p>',
    ],

    // 7. Communication & Mobile Access
    [
        'title' => '[Student] Accessing PRESOLA via the Moodle Mobile App',
        'content' => '<h3>Welcome to the PRESOLA Helpdesk</h3>
<p>You can access your distance learning materials on the go using the official Moodle Mobile App.</p>
<h4>Setup Instructions:</h4>
<ol>
<li>Download the "Moodle" app from the Apple App Store or Google Play Store.</li>
<li>Open the app. When asked for your site address, enter: <strong>https://ecampuspjj.president.ac.id</strong></li>
<li>You will be redirected to the PRESOLA SSO login page.</li>
<li>Enter your standard PRESOLA email and password.</li>
<li>Once logged in, you can download course materials (PDFs) for offline viewing and receive push notifications for upcoming deadlines.</li>
</ol>',
    ],
    [
        'title' => '[Teacher] Sending Announcements and Direct Messages',
        'content' => '<h3>Welcome to the PRESOLA Helpdesk</h3>
<p>Communication is the key to successful distance learning. PRESOLA provides two main ways to reach your students.</p>
<h4>1. Course Announcements (One-to-Many)</h4>
<p>Use the <strong>Announcements</strong> forum at the top of your course to broadcast important updates (e.g., class cancellations, deadline extensions). Every student enrolled in the course will receive an email copy of your post automatically within 30 minutes.</p>
<h4>2. Direct Messaging (One-to-One)</h4>
<p>If you need to contact a specific student regarding their progress, go to <strong>Participants</strong>, click their name, and click <strong>Message</strong>. This opens the Moodle chat interface. The student will see a notification bubble the next time they log in to PRESOLA.</p>',
    ]
];

cli_heading("Starting AuraSupport (PRESOLA) KB Bulk Import...");

$success_count = 0;
$fail_count = 0;

foreach ($articles as $article) {
    $record = new stdClass();
    $record->title = $article['title'];
    $record->content = $article['content'];
    $record->authorid = $admin->id;
    $record->timecreated = $time;
    $record->timemodified = $time;

    try {
        $DB->insert_record('local_aurasupport_kb', $record);
        $success_count++;
        mtrace("Inserted: " . $article['title']);
    } catch (Exception $e) {
        $fail_count++;
        mtrace("Failed to insert: " . $article['title'] . " - " . $e->getMessage());
    }
}

mtrace("\nDone! Successfully imported $success_count articles. Failed: $fail_count.");
