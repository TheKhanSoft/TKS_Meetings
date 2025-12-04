<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HelpCategory;
use App\Models\HelpArticle;
use Illuminate\Support\Str;

class HelpSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data to avoid duplicates
        HelpArticle::truncate();
        HelpCategory::truncate();

        // 1. Getting Started
        $cat1 = HelpCategory::create([
            'name' => 'Getting Started',
            'slug' => 'getting-started',
            'description' => 'New to {{ app_name }}? Start here to learn the basics.',
            'order' => 1,
            'is_active' => true,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat1->id,
            'title' => 'Welcome to {{ app_name }}',
            'slug' => 'welcome-to-academics',
            'content' => "
# Welcome to {{ app_name }}

{{ app_name }} is your comprehensive solution for managing academic meetings, agendas, and minutes. This platform is designed to streamline the administrative processes of academic institutions, ensuring that governance is efficient, transparent, and accessible.

## Key Features

*   **Meeting Management**: Schedule and organize meetings with ease.
*   **Agenda Builder**: collaboratively build agendas and track item status.
*   **Minute Taking**: Record minutes in real-time and assign action items.
*   **User Roles**: Granular permission control for administrators, staff, and faculty.
*   **Analytics**: Visualize meeting frequency and agenda completion rates.

## How to use this Help Center

Use the sidebar to navigate through different categories. You can also use the search bar at the top of the Help Center to find specific answers to your questions.
            ",
            'is_published' => true,
            'order' => 0,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat1->id,
            'title' => 'The Meeting Lifecycle',
            'slug' => 'meeting-lifecycle',
            'content' => "
# The Meeting Lifecycle

Understanding the flow of a meeting in {{ app_name }} helps you manage your time effectively.

### 1. Preparation (Pre-Meeting)
*   **Schedule**: Create the meeting record.
*   **Agenda**: Build the agenda items and attach documents.
*   **Notify**: Send invitations to attendees.

### 2. Execution (During Meeting)
*   **Attendance**: Mark who is present.
*   **Minutes**: Record discussions and decisions in real-time.
*   **Action Items**: Assign tasks as they arise.

### 3. Finalization (Post-Meeting)
*   **Review**: Edit minutes for clarity.
*   **Approval**: Submit minutes for formal approval (if required).
*   **Archive**: The meeting becomes a permanent record.

This cycle ensures that governance is continuous and well-documented.
            ",
            'is_published' => true,
            'order' => 1,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat1->id,
            'title' => 'Navigating the Dashboard',
            'slug' => 'navigating-the-dashboard',
            'content' => "
# Navigating the Dashboard

Your dashboard is the command center of {{ app_name }}. Here's a breakdown of what you see when you log in:

### 1. Activity Overview
The main chart displays meeting activity over time. You can filter this by:
*   **Timeframe**: This month, last month, this year, etc.
*   **Meeting Type**: Filter by specific committees or groups.

### 2. Quick Stats
At the top, you'll find key metrics:
*   **Total Meetings**: All-time count of meetings.
*   **Scheduled This Month**: Upcoming workload.
*   **Pending Agenda**: Items that need discussion.
*   **Completion Rate**: Percentage of agenda items marked as 'Discussed'.

### 3. Action Items
The **'Requires Your Attention'** card lists minutes or tasks assigned to you that are pending approval or completion. Click on any item to jump directly to the details.

### 4. Upcoming Schedule
A list of your next 5 meetings, showing the date, time, and meeting type.
            ",
            'is_published' => true,
            'order' => 2,
        ]);

        // 2. Meeting Management
        $cat2 = HelpCategory::create([
            'name' => 'Meeting Management',
            'slug' => 'meeting-management',
            'description' => 'Learn how to schedule and organize meetings.',
            'order' => 2,
            'is_active' => true,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat2->id,
            'title' => 'Scheduling a New Meeting',
            'slug' => 'scheduling-a-new-meeting',
            'content' => "
# Scheduling a New Meeting

To schedule a meeting, follow these steps:

1.  Navigate to **Meetings** in the sidebar.
2.  Click the **New Meeting** button in the top right corner.
3.  Fill in the required details:
    *   **Title**: A descriptive name for the meeting (e.g., 'Faculty Senate - Oct 2023').
    *   **Type**: Select the committee or meeting type.
    *   **Date & Time**: When the meeting will occur.
    *   **Location**: Physical room or virtual link.
4.  Click **Save**.

Once created, you can begin adding agenda items to the meeting.
            ",
            'is_published' => true,
            'order' => 1,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat2->id,
            'title' => 'Meeting Roles Explained',
            'slug' => 'meeting-roles-explained',
            'content' => "
# Meeting Roles Explained

When creating a meeting, you can assign specific roles to users to ensure accountability and proper workflow.

### Key Roles

*   **Director**: The person responsible for the overall direction of the meeting or committee.
*   **Registrar**: The administrative officer responsible for records and compliance.
*   **VC (Vice Chancellor)**: The executive head, often required for high-level academic meetings.
*   **Entry By**: The user who created the meeting record (automatically assigned).

Assigning these roles helps in generating accurate reports and ensuring that the right people are notified of meeting details.
            ",
            'is_published' => true,
            'order' => 2,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat2->id,
            'title' => 'Editing or Canceling a Meeting',
            'slug' => 'editing-canceling-meeting',
            'content' => "
# Editing or Canceling a Meeting

Plans change, and sometimes you need to update meeting details or cancel it entirely.

### Editing a Meeting
1.  Navigate to the **Meetings** list.
2.  Click on the meeting you wish to edit.
3.  Click the **Edit** button (pencil icon) usually found near the meeting title.
4.  Update the necessary fields (Date, Time, Location, etc.).
5.  Click **Save**.

### Canceling/Deleting a Meeting
**Warning**: Deleting a meeting is permanent and will remove all associated agenda items and minutes.

1.  Go to the **Meeting Details** page.
2.  Click the **Delete** button (trash icon).
3.  Confirm the action in the popup dialog.

If you only want to postpone the meeting, consider editing the date instead of deleting it.
            ",
            'is_published' => true,
            'order' => 3,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat2->id,
            'title' => 'Understanding Meeting Types',
            'slug' => 'understanding-meeting-types',
            'content' => "
# Understanding Meeting Types

{{ app_name }} supports various meeting types to categorize your governance activities.

### Common Meeting Types
*   **Faculty Senate**: High-level academic governance meetings.
*   **Departmental Meeting**: Internal meetings for specific departments.
*   **Board of Trustees**: Strategic planning and oversight meetings.
*   **Committee Meeting**: Focused groups working on specific tasks (e.g., Curriculum Committee).

### Why it Matters
Filtering by meeting type allows you to generate specific reports. For example, you can see how many 'Faculty Senate' meetings were held this year versus 'Departmental Meetings'.
            ",
            'is_published' => true,
            'order' => 4,
        ]);

        // 3. Agendas & Minutes
        $cat3 = HelpCategory::create([
            'name' => 'Agendas & Minutes',
            'slug' => 'agendas-and-minutes',
            'description' => 'Managing the core content of your meetings.',
            'order' => 3,
            'is_active' => true,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat3->id,
            'title' => 'The Agenda Workflow',
            'slug' => 'agenda-workflow',
            'content' => "
# The Agenda Workflow

Agenda items in {{ app_name }} follow a specific lifecycle to ensure nothing gets lost.

### Statuses

1.  **Pending**: The item has been proposed but not yet discussed.
2.  **Discussed**: The item was covered during the meeting.
3.  **Deferred**: The item was moved to a future meeting.
4.  **Withdrawn**: The item was removed from consideration.

### Adding Items
You can add items directly from the **Meeting Details** page or via the **Agendas** menu item. When adding an item, you can attach files and descriptions to provide context for attendees.
            ",
            'is_published' => true,
            'order' => 1,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat3->id,
            'title' => 'Recording Minutes',
            'slug' => 'recording-minutes',
            'content' => "
# Recording Minutes

During or after a meeting, the secretary or designated note-taker can record minutes.

1.  Go to the **Meeting Details** page.
2.  Scroll to the **Minutes** section.
3.  Click **Add Minute**.
4.  Select the related **Agenda Item**.
5.  Enter the content of the discussion/decision.
6.  (Optional) Assign a **Responsible User** if there is a follow-up task.

### Approvals
If a minute is assigned to a user, it will appear in their dashboard under 'Requires Your Attention'. They can then mark it as completed or approved.
            ",
            'is_published' => true,
            'order' => 2,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat3->id,
            'title' => 'Understanding Agenda Item Types',
            'slug' => 'agenda-item-types',
            'content' => "
# Understanding Agenda Item Types

Categorizing agenda items helps in structuring the meeting efficiently.

### Common Types

*   **Information**: Items meant for updates or announcements. No decision is required.
*   **Discussion**: Items that require debate or brainstorming but may not result in an immediate vote.
*   **Decision/Action**: Items that require a formal vote or a specific action to be taken.
*   **Ratification**: Approving actions taken previously or by a sub-committee.

Selecting the correct type helps attendees prepare for the meeting and understand what is expected of them for each item.
            ",
            'is_published' => true,
            'order' => 3,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat3->id,
            'title' => 'Reordering Agenda Items',
            'slug' => 'reordering-agenda-items',
            'content' => "
# Reordering Agenda Items

The flow of a meeting is crucial. You can easily reorder agenda items to suit your needs.

1.  Go to the **Meeting Details** page.
2.  Scroll to the **Agenda** section.
3.  Look for the **drag handle** (usually six dots or lines) on the left side of each agenda item row.
4.  Click and hold the handle.
5.  Drag the item to its new position.
6.  Release the mouse button.

The new order is automatically saved.
            ",
            'is_published' => true,
            'order' => 4,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat3->id,
            'title' => 'Attaching Files to Agenda Items',
            'slug' => 'attaching-files-agenda',
            'content' => "
# Attaching Files to Agenda Items

Supporting documents are essential for informed discussions.

### How to Attach a File
1.  When creating or editing an **Agenda Item**, look for the **Attachments** section.
2.  Click **Choose File** or drag and drop your document into the designated area.
3.  Supported formats usually include PDF, DOCX, XLSX, and PPTX.
4.  Click **Save**.

### Viewing Attachments
Attendees can view attachments by clicking the file icon next to the agenda item in the meeting view.
            ",
            'is_published' => true,
            'order' => 5,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat3->id,
            'title' => 'Tracking Action Items',
            'slug' => 'tracking-action-items',
            'content' => "
# Tracking Action Items

Minutes often result in tasks that need to be completed.

### Assigning an Action Item
When recording a minute:
1.  Select the **Responsible User** from the dropdown menu.
2.  (Optional) Set a **Due Date** in the description.

### Monitoring Progress
*   **For the Assignee**: The task appears on their Dashboard under 'Requires Your Attention'.
*   **For the Admin**: You can view open tasks in the **Reports** section or by filtering minutes by status.

Once a task is done, the assignee can mark it as **Complete**, which updates the meeting record.
            ",
            'is_published' => true,
            'order' => 6,
        ]);

        // 4. User Management
        $cat4 = HelpCategory::create([
            'name' => 'User Management',
            'slug' => 'user-management',
            'description' => 'For Administrators: Managing access and roles.',
            'order' => 4,
            'is_active' => true,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat4->id,
            'title' => 'Managing Users and Roles',
            'slug' => 'managing-users-roles',
            'content' => "
# Managing Users and Roles

Administrators have full control over user access.

### Creating a User
1.  Go to **Administration > Users**.
2.  Click **New User**.
3.  Enter their name, email, and initial password.
4.  Assign **Roles** (e.g., Admin, Staff, Faculty).
5.  Assign an **Employment Status**.

### Positions
Users can hold multiple positions over time (e.g., 'Department Chair', 'Committee Member'). You can manage this history in the **Positions** tab of a user's profile. This is crucial for tracking historical governance data.
            ",
            'is_published' => true,
            'order' => 1,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat4->id,
            'title' => 'Deactivating Users',
            'slug' => 'deactivating-users',
            'content' => "
# Deactivating Users

When a staff member leaves or no longer requires access, you should deactivate their account rather than deleting it to preserve historical data.

1.  Navigate to **Administration > Users**.
2.  Search for the user.
3.  Click **Edit**.
4.  Toggle the **Active** status to **Off**.
5.  Click **Save**.

The user will no longer be able to log in, but their name will still appear on past meeting minutes and agendas.
            ",
            'is_published' => true,
            'order' => 2,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat4->id,
            'title' => 'Understanding Permissions',
            'slug' => 'understanding-permissions',
            'content' => "
# Understanding Permissions

{{ app_name }} uses a role-based permission system.

### Common Roles & Capabilities

*   **Super Admin**: Full access to all settings, users, and data. Can delete records.
*   **Admin**: Can manage meetings, users, and content but may be restricted from system-wide configuration.
*   **Staff/Editor**: Can create meetings and agendas but cannot manage users.
*   **Faculty/Viewer**: Read-only access to meetings and minutes relevant to them.

Check with your system administrator for the specific configuration of your institution.
            ",
            'is_published' => true,
            'order' => 3,
        ]);

        // 5. Account Settings
        $cat5 = HelpCategory::create([
            'name' => 'Account Settings',
            'slug' => 'account-settings',
            'description' => 'Manage your profile and security.',
            'order' => 5,
            'is_active' => true,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat5->id,
            'title' => 'Two-Factor Authentication',
            'slug' => 'two-factor-authentication',
            'content' => "
# Two-Factor Authentication (2FA)

We highly recommend enabling 2FA to secure your account.

1.  Click on your name in the sidebar to go to **Profile**.
2.  Navigate to the **Two-Factor Authentication** section.
3.  Click **Enable**.
4.  Scan the QR code with your authenticator app (Google Authenticator, Authy, etc.).
5.  Enter the confirmation code.

Once enabled, you will need to provide a code from your phone whenever you log in.
            ",
            'is_published' => true,
            'order' => 1,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat5->id,
            'title' => 'Updating Profile Information',
            'slug' => 'updating-profile',
            'content' => "
# Updating Profile Information

Keep your contact details up to date.

1.  Click on your name in the sidebar (or top right corner).
2.  Select **Profile**.
3.  Here you can update:
    *   **Name**: Your display name.
    *   **Email**: The address used for notifications and login.
    *   **Photo**: Upload a professional headshot.
4.  Click **Save** after making changes.
            ",
            'is_published' => true,
            'order' => 2,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat5->id,
            'title' => 'Changing Password',
            'slug' => 'changing-password',
            'content' => "
# Changing Password

Regularly updating your password helps keep your account secure.

1.  Go to **Profile**.
2.  Scroll to **Update Password**.
3.  Enter your **Current Password**.
4.  Enter your **New Password** and confirm it.
5.  Click **Save**.

Ensure your new password is strong (at least 8 characters, mixing letters, numbers, and symbols).
            ",
            'is_published' => true,
            'order' => 4,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat5->id,
            'title' => 'Data Privacy & Security',
            'slug' => 'data-privacy-security',
            'content' => "
# Data Privacy & Security

We take the security of your data seriously.

*   **Encryption**: All data is encrypted in transit (HTTPS) and at rest.
*   **Access Control**: Strict role-based access ensures only authorized users see sensitive data.
*   **Audit Logs**: Critical actions are logged for accountability.

If you suspect a security breach, contact the system administrator immediately.
            ",
            'is_published' => true,
            'order' => 5,
        ]);

        // 6. Announcements
        $cat6 = HelpCategory::create([
            'name' => 'Announcements',
            'slug' => 'announcements-help',
            'description' => 'Broadcasting information to all users.',
            'order' => 6,
            'is_active' => true,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat6->id,
            'title' => 'Creating and Managing Announcements',
            'slug' => 'creating-announcements',
            'content' => "
# Creating and Managing Announcements

Announcements are a great way to share important news with all users of the platform. They appear on the Dashboard and in the Announcements menu.

### Creating an Announcement
1.  Navigate to **Announcements** in the sidebar.
2.  Click **New Announcement**.
3.  Enter a **Title** and **Content**.
4.  (Optional) Set a **Publish Date** (when it starts showing) and **Expiration Date** (when it stops showing).
5.  Toggle **Active** to make it visible.
6.  Click **Save**.

### Managing Visibility
*   **Active**: The announcement is live (subject to dates).
*   **Inactive**: The announcement is hidden, regardless of dates.
*   **Dates**: If left blank, the announcement is always visible while Active.
            ",
            'is_published' => true,
            'order' => 1,
        ]);

        // 7. Reports & Data
        $cat7 = HelpCategory::create([
            'name' => 'Reports & Data',
            'slug' => 'reports-and-data',
            'description' => 'Exporting data and understanding analytics.',
            'order' => 7,
            'is_active' => true,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat7->id,
            'title' => 'Exporting Minutes and Agendas',
            'slug' => 'exporting-minutes-agendas',
            'content' => "
# Exporting Minutes and Agendas

You can download meeting documentation in various formats for offline use or official records.

### How to Export
1.  Go to the **Meeting Details** page.
2.  Look for the **Export** or **Download** buttons near the Agenda or Minutes sections.
3.  Select your preferred format:
    *   **PDF**: Best for printing and sharing.
    *   **Word (DOCX)**: Best for editing or formatting.
    *   **HTML**: For viewing in a browser.

### Bulk Exports
On the Dashboard, you can also export summary reports of meeting statistics by clicking the download icon on the 'Types Breakdown' card.
            ",
            'is_published' => true,
            'order' => 1,
        ]);

        // 8. Troubleshooting
        $cat8 = HelpCategory::create([
            'name' => 'Troubleshooting',
            'slug' => 'troubleshooting',
            'description' => 'Solutions to common problems.',
            'order' => 8,
            'is_active' => true,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat8->id,
            'title' => 'Resetting Your Password',
            'slug' => 'resetting-password',
            'content' => "
# Resetting Your Password

If you have forgotten your password:

1.  Go to the login page.
2.  Click **Forgot your password?**.
3.  Enter your email address.
4.  Check your email for a password reset link.
5.  Follow the link to set a new password.

If you are logged in and want to change it:
1.  Go to **Profile > Update Password**.
2.  Enter your current password and your new password.
3.  Click **Save**.
            ",
            'is_published' => true,
            'order' => 1,
        ]);

        // 9. Using the Help Center
        $cat9 = HelpCategory::create([
            'name' => 'Using the Help Center',
            'slug' => 'using-help-center',
            'description' => 'How to get the most out of this support portal.',
            'order' => 9,
            'is_active' => true,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat9->id,
            'title' => 'Finding Answers',
            'slug' => 'finding-answers',
            'content' => "
# Finding Answers in the Help Center

We've designed this Help Center to be as intuitive as possible.

### Browsing Categories
On the main Help Center page, you'll see a list of categories on the left (or top on mobile). Click on a category to filter the articles. This is the best way to explore related topics.

### Search
Use the large search bar at the top of the page. As you type, the system will instantly filter articles that match your keywords in either the title or the content.

### Popular Articles
The home page also displays a list of the most frequently viewed articles, which might contain the answer you're looking for.
            ",
            'is_published' => true,
            'order' => 1,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat9->id,
            'title' => 'Providing Feedback',
            'slug' => 'providing-feedback',
            'content' => "
# Providing Feedback

Your feedback helps us improve our documentation.

### Voting on Articles
At the bottom of every article, you will see a section asking **'Was this article helpful?'**.
*   Click **Yes** if the article answered your question.
*   Click **No** if it didn't.

We review these votes regularly to identify which articles need improvement or clarification.
            ",
            'is_published' => true,
            'order' => 2,
        ]);
        // 10. System Settings
        $cat10 = HelpCategory::create([
            'name' => 'System Settings',
            'slug' => 'system-settings',
            'description' => 'Configuration options for the entire platform.',
            'order' => 10,
            'is_active' => true,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat10->id,
            'title' => 'General System Configuration',
            'slug' => 'general-system-configuration',
            'content' => "
# General System Configuration

Administrators can configure the core identity and behavior of the {{ app_name }} platform.

### Branding
Navigate to **Settings > System** to update:
*   **Site Name**: The name displayed in the browser tab and header.
*   **Organization Name**: Used in official reports and footers.
*   **Logo URL**: A link to your institution's logo.
*   **Primary Color**: The main accent color used throughout the application.

### Localization
Ensure the system matches your region:
*   **Timezone**: Critical for accurate meeting scheduling.
*   **Date & Time Format**: Choose how dates are displayed (e.g., 'M d, Y').
*   **Default Locale**: The default language for new users.
            ",
            'is_published' => true,
            'order' => 1,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat10->id,
            'title' => 'Managing System Features',
            'slug' => 'managing-system-features',
            'content' => "
# Managing System Features

You can toggle specific features on or off depending on your needs.

### Feature Toggles
*   **Allow Registration**: If disabled, only administrators can create new user accounts. Useful for closed internal systems.
*   **Enable Announcements**: Shows or hides the Announcements menu item.
*   **Theme Toggle**: Allows users to switch between light and dark modes.

### Maintenance Mode
**Warning**: Enabling Maintenance Mode will prevent all non-admin users from accessing the system. Use this only when performing updates or critical maintenance.
            ",
            'is_published' => true,
            'order' => 2,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat10->id,
            'title' => 'Managing Help Categories',
            'slug' => 'managing-help-categories',
            'content' => "
# Managing Help Categories

Organize the Help Center content.

1.  Go to **Administration > Help Center > Categories**.
2.  **Reorder**: Drag and drop categories to change their display order.
3.  **Add Category**: Create a new section for documentation.
4.  **Visibility**: Toggle 'Active' status to hide entire sections from users.

Keep categories broad (e.g., 'Meetings', 'User Management') to avoid clutter.
            ",
            'is_published' => true,
            'order' => 14,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat10->id,
            'title' => 'Writing Help Articles',
            'slug' => 'writing-help-articles',
            'content' => "
# Writing Help Articles

Create documentation for your users.

1.  Go to **Administration > Help Center > Articles**.
2.  Click **New Article**.
3.  **Editor**: Use the Markdown editor to format text.
    *   Use `#` for headers.
    *   Use `*` for bullet points.
4.  **Category**: Assign the article to a relevant category.
5.  **Publish**: Toggle 'Published' to make it visible immediately.

Good articles are short, use screenshots, and focus on a single task.
            ",
            'is_published' => true,
            'order' => 15,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat10->id,
            'title' => 'System Settings Configuration',
            'slug' => 'system-settings-configuration',
            'content' => "
# System Settings Configuration

Global controls for the {{ app_name }} platform.

1.  Navigate to **Administration > Settings**.
2.  **General**: Set the Institution Name, Logo, and Timezone.
3.  **Security**: Configure Password policies and Session timeouts.
4.  **Email**: Set up SMTP details for outgoing notifications.
5.  **Maintenance Mode**: Toggle this to block user access during upgrades.

**Warning**: Changing these settings affects all users immediately.
            ",
            'is_published' => true,
            'order' => 16,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat10->id,
            'title' => 'System Logs & Auditing',
            'slug' => 'system-logs-auditing',
            'content' => "
# System Logs & Auditing

Monitor system activity for security and troubleshooting.

1.  Go to **Administration > Logs**.
2.  **Activity Log**: View a chronological list of user actions (Login, Create, Update, Delete).
3.  **Error Log**: View system exceptions and stack traces for debugging.
4.  **Filter**: Search by User ID, Date, or Action Type.

Logs are retained for 90 days by default.
            ",
            'is_published' => true,
            'order' => 17,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat10->id,
            'title' => 'Publishing Announcements',
            'slug' => 'publishing-announcements',
            'content' => "
# Publishing Announcements

Broadcast messages to all users on their dashboard.

1.  Go to **Administration > Announcements**.
2.  Click **Create Announcement**.
3.  **Content**: Enter the Title and Body.
4.  **Type**: Choose 'Info', 'Warning', or 'Critical' (affects the color).
5.  **Schedule**: Set a 'Start Date' and 'End Date' for visibility.
6.  **Target**: Optionally limit to specific User Groups.

Active announcements appear at the top of the User Dashboard.
            ",
            'is_published' => true,
            'order' => 18,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat4->id,
            'title' => 'User Position Assignment',
            'slug' => 'user-position-assignment',
            'content' => "
# User Position Assignment

Link users to their professional titles.

1.  Go to **Administration > Users**.
2.  Select a **User**.
3.  Navigate to the **Positions** tab.
4.  Click **Assign Position**.
5.  Select the **Position** (e.g., 'Lecturer') and **Department**.
6.  Set the **Start Date** (and End Date if temporary).

A user can hold multiple positions simultaneously (e.g., 'Head of Dept' and 'Professor').
            ",
            'is_published' => true,
            'order' => 8,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat4->id,
            'title' => 'Understanding User Roles',
            'slug' => 'understanding-user-roles',
            'content' => "
# Understanding User Roles

Permissions in Academics are role-based.

*   **Super Admin**: Full access to all settings, data, and logs.
*   **Admin**: Can manage users, meetings, and content, but not system configuration.
*   **Chairperson**: Can create meetings, approve minutes, and manage agendas.
*   **Member**: Can view meetings, download documents, and mark attendance.
*   **Guest**: Read-only access to specific shared items.

Roles are assigned in the User Profile by an Administrator.
            ",
            'is_published' => true,
            'order' => 9,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat3->id,
            'title' => 'Agenda Item Lifecycle',
            'slug' => 'agenda-item-lifecycle',
            'content' => "
# Agenda Item Lifecycle

The stages of an agenda item.

1.  **Proposed**: Item is suggested by a member.
2.  **Pending Review**: Chairperson reviews the suggestion.
3.  **Accepted**: Added to the formal agenda.
4.  **Deferred**: Moved to a future meeting.
5.  **Discussed**: Addressed during the meeting.
6.  **Closed**: All actions related to the item are complete.

Tracking status helps prevent topics from being forgotten.
            ",
            'is_published' => true,
            'order' => 12,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat3->id,
            'title' => 'Minute Versioning',
            'slug' => 'minute-versioning',
            'content' => "
# Minute Versioning

Tracking changes to official records.

*   **Auto-Save**: Creates temporary checkpoints while editing.
*   **Versions**: Every time you 'Publish' or 'Update' minutes, a new version is stored.
*   **History**: Click **Version History** in the minutes editor to see who changed what and when.
*   **Restore**: You can revert to a previous version if a mistake was made.

This ensures the integrity of the official record.
            ",
            'is_published' => true,
            'order' => 13,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat2->id,
            'title' => 'Meeting Status Workflow',
            'slug' => 'meeting-status-workflow',
            'content' => "
# Meeting Status Workflow

How a meeting progresses through the system.

1.  **Scheduled**: Date and time set, invites sent.
2.  **Confirmed**: Quorum is expected.
3.  **In Progress**: Meeting is currently happening (activates 'Live Minute' features).
4.  **Held**: Meeting finished, minutes are being drafted.
5.  **Closed**: Minutes approved and archived.
6.  **Cancelled**: Meeting called off (notifications sent to attendees).
            ",
            'is_published' => true,
            'order' => 10,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat2->id,
            'title' => 'Archiving Meetings',
            'slug' => 'archiving-meetings',
            'content' => "
# Archiving Meetings

Managing historical data.

*   **Automatic**: Meetings are considered 'Archived' once their minutes are approved and the academic year ends.
*   **Access**: Archived meetings are read-only.
*   **Retrieval**: Use the **Archives** filter in the meeting list to view records older than 2 years.
*   **Retention**: Data is kept according to the institution's retention policy (configured in Settings).
            ",
            'is_published' => true,
            'order' => 11,
        ]);

        // 11. Notifications
        $cat11 = HelpCategory::create([
            'name' => 'Notifications',
            'slug' => 'notifications',
            'description' => 'Managing your alerts and messages.',
            'order' => 11,
            'is_active' => true,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat11->id,
            'title' => 'Notification Triggers',
            'slug' => 'notification-triggers',
            'content' => "
# Notification Triggers

When does the system send emails?

*   **New Meeting**: Immediately upon publication of a meeting.
*   **Agenda Update**: If an agenda item is added < 24 hours before the meeting.
*   **Minutes for Review**: When the secretary submits a draft.
*   **Task Assignment**: When you are tagged in an Action Item.
*   **Reminder**: 1 hour before the meeting start time.

You can manage your subscription to these in **Profile > Notifications**.
            ",
            'is_published' => true,
            'order' => 4,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat7->id,
            'title' => 'Exporting Agenda Items',
            'slug' => 'exporting-agenda-items',
            'content' => "
# Exporting Agenda Items

Get your data out of the system.

1.  Go to **Reports > Agenda Items**.
2.  **Filter**: Select Date Range, Meeting Type, or Status.
3.  **Export**: Click **Export to CSV** or **Export to PDF**.
4.  **Use Case**: Useful for compiling an 'Annual Report' of all decisions made by a committee.
            ",
            'is_published' => true,
            'order' => 4,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat5->id,
            'title' => 'User Profile Management',
            'slug' => 'user-profile-management',
            'content' => "
# User Profile Management

Keep your personal details up to date.

1.  Click your **Avatar** in the top right.
2.  Select **Profile**.
3.  **Edit Details**: Update your Phone Number, Office Location, or Bio.
4.  **Avatar**: Upload a professional photo.
5.  **Password**: Change your login password securely.

Note: You cannot change your email address or Role; contact an Admin for that.
            ",
            'is_published' => true,
            'order' => 7,
        ]);



        HelpArticle::create([
            'help_category_id' => $cat4->id,
            'title' => 'Importing Users',
            'slug' => 'importing-users',
            'content' => "
# Importing Users

Bulk create user accounts.

1.  Prepare a **CSV file** with columns: `name`, `email`, `password` (optional), `role`.
2.  Go to **Admin > Users > Import**.
3.  Upload the file.
4.  **Map Fields**: Ensure the CSV columns match the system fields.
5.  **Run Import**: The system will process the rows.
6.  **Errors**: Any failed rows will be reported in a downloadable error log.

This is the fastest way to onboard new staff at the start of a semester.
            ",
            'is_published' => true,
            'order' => 10,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat10->id,
            'title' => 'Interface Customization',
            'slug' => 'interface-customization',
            'content' => "
# Interface Customization

Tailor the look and feel of {{ app_name }}.

### Display Options
*   **Pagination Size**: Control how many rows appear in data tables (default is 15).
*   **Footer Text**: Custom text displayed at the bottom of the sidebar (e.g., copyright or support phone number).
*   **Theme**: Choose the default visual theme (Light or Dark) for new users.
*   **Theme Toggle**: Allow users to switch themes themselves.
            ",
            'is_published' => true,
            'order' => 19,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat10->id,
            'title' => 'Localization Settings',
            'slug' => 'localization-settings',
            'content' => "
# Localization Settings

Adapt {{ app_name }} to your region.

*   **Date Format**: Define how dates are displayed (e.g., `Y-m-d` for 2023-12-31).
*   **Time Format**: Choose between 12-hour (`g:i A`) or 24-hour (`H:i`) formats.
*   **Default Locale**: The language used for system labels (e.g., `en` for English).
            ",
            'is_published' => true,
            'order' => 20,
        ]);

        HelpArticle::create([
            'help_category_id' => $cat10->id,
            'title' => 'Support Configuration',
            'slug' => 'support-configuration',
            'content' => "
# Support Configuration

Connect users with help.

*   **Help URL**: If you have an external wiki or support portal, enter the URL here. A 'Help' link will appear in the sidebar.
*   **Contact Email**: The email address displayed in error messages or support footers.
            ",
            'is_published' => true,
            'order' => 21,
        ]);
    }
}
