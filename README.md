# Moodle Local AuraSupport (Ultimate Edition)

**AuraSupport** is a cutting-edge, all-in-one Helpdesk, Ticketing, and Business Intelligence (BI) plugin designed specifically for Moodle. It combines the core ticketing concepts of traditional helpdesk systems with advanced data analytics and modern Artificial Intelligence (AI) to create a seamless support ecosystem for educational institutions.

This plugin was developed as a comprehensive alternative to using multiple separate plugins (like `local_helpdesk` and `local_kopere_bi`), natively bringing all their powerful features under one clean, modern, and highly optimized roof.

---

## 🌟 Comprehensive Features

### 1. Advanced Ticket Management System
A robust system designed to handle user inquiries, complaints, and IT support efficiently:
- **Course Integration:** Tickets can be optionally linked directly to specific Moodle Courses (`courseid`), giving agents immediate context on where the user is experiencing issues.
- **Department Routing:** Categorize tickets by physical or administrative units (e.g., IT Support, Finance, Academic).
- **Admin Delegation:** Administrators and Support Agents can create tickets on behalf of users (useful for phone or walk-in support).
- **Priority Levels:** Organize tickets by priority (Low, Medium, High, Urgent) to ensure critical issues are handled first.
- **SLA Tracking:** Tracks creation time, update time, and resolution time to measure support speed.

### 2. 🤖 Gemini AI Auto-Response (Built-in)
Natively integrated with Google's latest **Generative Language API**, empowering your support team with AI:
- **Instant Drafting:** Agents have access to a **"✨ Draft Response with Gemini AI"** button inside every ticket.
- **Context Aware:** The AI autonomously reads the ticket subject, description, and the entire conversation history to generate a highly professional, empathetic, and structured HTML draft response.
- **Multiple Models Supported:** Administrators can select their preferred engine from the settings, including:
  - *Gemini 3.5 Flash (Most Intelligent Agentic)*
  - *Gemini 3.1 Pro (Advanced Intelligence)*
  - *Gemini 2.5 Flash (Best Price-Performance)*
  - *...and many more.*

### 3. 📊 Ultimate BI Dashboard (12 Interactive Reports)
AuraSupport includes a beautiful Business Intelligence dashboard powered by [**ApexCharts**](https://apexcharts.com/). The dashboard visualizes real-time support metrics without needing external BI tools:
- **Area/Line Charts**: Visualizing ticket trends over the last 30 days.
- **Donut Charts**: Status distribution (Open vs. Resolved vs. Closed).
- **Pie Charts**: Visualizing the percentage distribution of ticket priorities.
- **Bar Charts**: Comparing ticket volume across different administrative Departments.
- **Column Charts**: Highlighting which Moodle Courses generate the most support requests.
- **SLA Speed Metric**: A dedicated block showing the *Average Resolution Time*.
- **Gamification**: A *Top Agents Leaderboard* ranking support staff by tickets resolved.
- **User Activity**: Tracking the *Most Active Users* creating tickets.
- **Recent Unresolved Tickets**: A tabular view of urgent tickets awaiting action.

### 4. 🗃️ Pure DataTables JS Integration
The ticket listing page (`tickets.php`) utilizes pure [**DataTables**](https://datatables.net/) for lightning-fast, client-side rendering. This provides clear and organized data visualization, including features such as:
- **Pagination**: Splits large datasets into pages for easier navigation.
- **Live Search**: Real-time filtering to quickly locate specific tickets by user, subject, or department.
- **Sorting**: Multi-column sorting capabilities for better data analysis.
- **Instant Export**: Data can be exported with a single click in various formats:
  - **Print**: Print the table directly from the browser while maintaining formatting.
  - **PDF**: Generates a PDF document of the table.
  - **Excel**: Exports data to an `.xlsx` file for further analysis.
  - **CSV**: Exports data in Comma-Separated Values format.
  - **Copy**: Copies data directly to the clipboard.

### 5. 📚 Knowledge Base (FAQ)
Reduce repetitive tickets by empowering users to find solutions independently:
- A built-in article system where admins can publish step-by-step guides.
- Users can browse the Knowledge Base before submitting a new ticket.

### 6. 📧 Automated Email Notifications
- Real-time email alerts sent to Agents when new tickets are assigned.
- Real-time email alerts sent to Users when their tickets receive a response or are marked as resolved.

---

## ⚙️ Installation Guide

### Prerequisites
- Moodle version 3.9 or higher (Requires `2020041500` core version).
- PHP 7.4 or higher.

### Steps
1. Download or clone this repository.
2. Rename the extracted folder to `aurasupport` (must be exact).
3. Move the `aurasupport` folder into the `local/` directory of your Moodle installation. The path should look like: `/path/to/moodle/local/aurasupport`.
4. Log in to your Moodle site as an **Administrator**.
5. Moodle will automatically detect the new plugin and prompt you to upgrade. Click **Upgrade Moodle database now** to run the installation scripts.
6. Once installed, the **AuraSupport** menu will appear in your Moodle Site Administration or Navigation block.

---

## 🛠️ Configuration & Setup

### 1. Setting Up Departments & Agents
Before users can submit tickets, you must create Departments and assign Agents.
- Navigate to the AuraSupport Dashboard and click on **Manage Departments**. Create your units (e.g., "IT Support").
- Click on **Manage Agents**. Search for a Moodle user and assign them to a Department. They will now have the capability to resolve tickets for that unit.

### 2. Enabling Gemini AI
To use the AI Auto-Response feature:
- Go to `Site Administration > Local plugins > AuraSupport`.
- Check the **Enable AI Auto-Response** box.
- Paste your **Gemini API Key** (Obtained from Google AI Studio).
- Select your preferred **Gemini Model** from the dropdown menu.
- Click **Save changes**.

---

## 📄 License & Copyright

**Copyright 2026 Tateta**  
Website: [samastanuswantara.com](https://samastanuswantara.com)

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
