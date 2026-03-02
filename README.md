# Advanced Typing Test Pro

[![WordPress Version](https://img.shields.io/badge/WordPress-5.0+-21759b.svg)](https://wordpress.org/)
[![License](https://img.shields.io/badge/License-GPL%20v2-blue.svg)]()
[![Version](https://img.shields.io/badge/Version-2.0.0-green.svg)]()

**Advanced Typing Test Pro** is a high-performance, professional-grade WordPress plugin. This tool is helpful for making websites for different types of typing speed testers and providing professional recognition to users. It features a robust administration system, dynamic passage selection based on difficulty, and an automated A4 certificate generation system.

---

## �️ Admin Side Features

- **Centralized Management**: Dedicated admin dashboard for managing all typing test content and settings.
- **Dynamic Multi-Language Support**: Create and manage unlimited languages using a custom taxonomy system.
- **Passage Content Management**: easily add, edit, or delete typing passages with full support for the WordPress block/classic editor.
- **Difficulty Grading**: A custom sidebar module to categorize passages into *Easy*, *Medium*, or *Hard* difficulty levels.
- **Shortcode Intelligence**: The settings page automatically generates a table of available shortcodes for every language you create.
- **Granular Settings Control**:
    - **Passing Criteria**: Set global minimum WPM and test duration requirements.
    - **Branding**: Customize the brand name that appears on top-tier certificates.
    - **Certificate Metadata**: Define issuer names, website links, and default PDF filenames.

## 👤 User Side Features

- **Professional Typing Interface**: A premium, responsive interface optimized for focus and speed.
- **Real-Time Analytics**:
    - **Live WPM**: Instant feedback on typing speed.
    - **Keystroke Accuracy**: Percentage tracking of correct inputs.
    - **Visual Counters**: Live display of correct vs. incorrect word counts.
- **Interactive Passage Control**:
    - **Difficulty Toggling**: Switch between levels instantly with intuitive UI buttons.
    - **Passage Selection**: Choose specific passages or let the system pick a random one for variety.
- **Intelligent Marking System**: Visual feedback (Green for correct, Red for wrong) that supports full backspacing and real-time correction.
- **Dynamic Certification Journey**:
    - **Tiered Progress**: Tracks completion of Easy, Medium, and Hard levels.
    - **Eligibility Engine**: The "Generate Certificate" button only unlocks once all passing criteria are met.
    - **Cross-Session Save**: Uses local storage to remember progress across different visits.
- **Premium PDF Certificates**: Generates a professional A4 PDF certificate featuring the user's name, speed/accuracy stats, date, and a unique certificate ID.

---

## 🛠 Installation

1.  **Download** the plugin zip file.
2.  Go to your WordPress Admin Dashboard > **Plugins** > **Add New**.
3.  Click **Upload Plugin** and select the `.zip` file.
4.  **Activate** the plugin.
5.  Navigate to the **Typing Test** menu in your dashboard.

---

## 📖 Setup Guide

### 1. Create Languages
Go to **Typing Test > Languages** and add the languages you want to support (e.g., "English", "Bangla"). Note the **Slug** generated for each language.

### 2. Add Typing Passages
Go to **Typing Test > Add New**. 
-   Enter a title and the passage content.
-   Select the **Language** from the sidebar.
-   Select the **Difficulty Level** (Easy, Medium, Hard).
-   Publish the content.

### 3. Configure Settings
Go to **Typing Test > Settings** to customize:
-   **Brand Name**: Your organization/site name for the certificate.
-   **Minimum WPM**: The speed required to "pass" a level.
-   **Minimum Duration**: The time required for a test attempt to count toward certification.
-   **Issuer Details**: Website URL and Issuer label for the certificate.

---

## ⌨️ Usage

To display the typing test for a specific language, use the following shortcode in any page or post:

```text
[typing_test language="your-language-slug"]
```

Example for English: `[typing_test language="english"]`
Example for Bangla: `[typing_test language="bangla"]`
---

## 📜 Technical Details

-   **Backend**: PHP (WordPress Custom Post Types, Taxonomies, and Settings API).
-   **Frontend**: JavaScript (jQuery), CSS3 (Flexbox/Grid).
-   **Libraries**: 
    -   `html2canvas`: For rendering certificate previews.
    -   `jsPDF`: For generating high-quality A4 PDF documents.
    -   *Note: These libraries are lazy-loaded only when the user generates a certificate to maintain optimal page load speeds.*

---

## 👤 Author

**Rayhan Hosen**
-   GitHub: [@rayhan-hosen](https://github.com/rayhan-hosen)

---

## 📄 License

This project is licensed under the GPLv2 or later License.
