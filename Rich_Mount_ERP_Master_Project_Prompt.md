# RICH MOUNT ERP — MASTER PROJECT PROMPT
## Client Requirements + Approved UI Reference + Clickable Demo + Backend Readiness

You are working on the **Rich Mount ERP System** for a Dubai/UAE client.

This prompt must be treated as the master project instruction.

There are THREE sources of truth:

1. **Client Requirements Document** — the uploaded Rich Mount ERP System Requirements & User Roles Specification.
2. **Approved UI/UX Reference** — the existing Rich Mount ERP dashboard/design pattern already created and approved.
3. **This Master Prompt** — explains how the requirements and approved design must be converted into a clickable, realistic demo and then into the production-ready application.

The client document is the functional source of truth.
The existing approved design is the visual source of truth.

Do not replace the client's requirements with assumptions.
Do not replace the approved UI with a completely different template.

---

# 1. FIRST TASK — READ AND UNDERSTAND THE CLIENT DOCUMENT

Before creating or changing anything:

- Read the complete client PDF.
- Understand every page.
- Extract every role.
- Extract every module.
- Extract every workflow.
- Extract every approval rule.
- Extract every branch requirement.
- Extract every financial rule.
- Extract every Zoho Books requirement.
- Extract every OCR requirement.
- Extract every reporting requirement.
- Extract every security requirement.
- Extract every mobile requirement.
- Extract every special UI/UX requirement.

Create an internal requirements map before implementation.

Do NOT skip any section.

The client document defines four primary roles:

- Super Admin
- Estimation User
- Project Engineer User
- Accountant

The client document describes different module access and responsibilities for each role.

---

# 2. SOURCE-OF-TRUTH PRIORITY

When making decisions use this priority:

## Priority 1 — Client Requirements

The client PDF controls:

- Business logic
- User roles
- Permissions
- Workflows
- Approval rules
- Branch logic
- Financial rules
- Zoho integration
- OCR
- Reporting
- Project lifecycle

## Priority 2 — Approved UI/UX Design

The existing design controls:

- Layout
- Navigation
- Sidebar
- Header
- Cards
- Tables
- Forms
- Colors
- Typography
- Spacing
- Buttons
- Modals
- Status badges
- Dashboard composition
- Interaction patterns

## Priority 3 — Technical Implementation Decisions

Only after understanding the first two should you choose implementation details.

Do not allow technical convenience to change business requirements or approved UX.

---

# 3. APPROVED DESIGN DIRECTION

The current Rich Mount ERP design follows a professional enterprise dashboard pattern inspired by the supplied reference.

Use:

Primary color:
#941C3C

Secondary / Dark:
#080A0B

Supporting colors:
- White
- Light gray
- Soft neutral backgrounds
- Semantic green
- Semantic amber
- Semantic red
- Semantic blue
- Soft purple where required

The visual language should be:

- Premium
- Professional
- Enterprise-grade
- Dubai/UAE business oriented
- Clean
- Data-focused
- Modern
- Minimal
- Highly readable

Do NOT use excessive gradients.

Do NOT use a generic CRM template.

Do NOT redesign the approved dashboard structure unnecessarily.

---

# 4. APPROVED DASHBOARD STRUCTURE

The dashboard design includes:

## Header

- Company logo
- Navigation/menu toggle
- Breadcrumb
- Global search
- Branch selector
- Notifications
- Settings
- User profile
- Role display
- Date/time
- Primary action

Example primary action:

+ New Lead

## Sidebar

Main navigation should include the approved ERP modules such as:

- Dashboard
- Lead Management
- Quotations
- Projects
- Expenses
- Finance
- Zoho Books
- Reports
- Users & Roles
- Branches
- Settings

The visible menu must change according to the logged-in user's permissions.

---

# 5. DASHBOARD FILTER AREA

Use a reusable filter bar.

Possible filters:

- Country
- Branch
- Department
- Project Status
- Engineer
- Date Range

Actions:

- Apply Filter
- Reset/Clear
- Filter settings
- Saved filter/bookmark where appropriate

Filters must be functional in the demo.

Do not make filters look functional but do nothing.

---

# 6. DASHBOARD KPI AREA

Use KPI cards similar to the approved reference.

Possible KPIs:

- Total Leads
- Quotations
- Active Projects
- Total Expenses
- Total Invoiced
- Pending Approvals
- Cash in Hand
- Pending Transactions

Each KPI card should be clickable where appropriate.

Clicking a KPI should navigate to the corresponding filtered module.

Example:

Total Leads → Lead Management with relevant filter.

---

# 7. DASHBOARD CHARTS

The dashboard may contain:

- Lead & Quotation Trend
- Project Status Overview
- Expense Overview
- Financial Trend
- Branch Performance

Charts must use realistic demo data.

Charts should respond to the dashboard filters.

If a chart has a date range or period selector, changing it must visibly update the demo data.

---

# 8. LEAD PIPELINE — REQUIRED DASHBOARD SECTION

Add the following section to the dashboard:

## Lead Pipeline

Four columns:

### New Leads

Example cards:

- Customer / Company
- Project subject
- Branch
- Date
- Assigned engineer
- More actions

Bottom action:

+ Add Lead

### Draft Quotations

Show leads/quotes currently in draft.

Bottom action:

+ Create Quotation

### Sent Quotations

Show quotations already sent.

Bottom action:

View Sent Quotations

### Awarded

Show awarded opportunities.

Bottom action:

View Awarded

The pipeline should visually communicate:

New Lead
→ Draft Quotation
→ Sent Quotation
→ Awarded

Cards should be clickable.

Clicking a card opens the corresponding Lead/Quote detail.

---

# 9. DASHBOARD LOWER SECTIONS

Use the approved dashboard pattern for:

## Recent Leads

Columns:

- #
- Lead Name
- Company
- Assigned To
- Source
- Status
- Date
- Action

Actions:

- View
- Edit where permitted
- More

## Upcoming Activities

Timeline-style section.

Examples:

- Meeting
- Quote review
- Site visit
- Expense approval
- Project activity

## Quick Actions

Examples:

- New Lead
- Create Quotation
- Add Project
- Add Expense
- Create Invoice
- Reports

Quick actions must respect user permissions.

---

# 10. ROLE-BASED DASHBOARDS

Do not create one identical dashboard for every role.

## Super Admin Dashboard

Show company-level information:

- Total Leads
- Quotes
- Awarded Projects
- Active Projects
- Expenses
- Invoices
- Pending Approvals
- Branch performance
- Financial summaries

## Estimation Dashboard

Focus on:

- Leads
- Draft Quotations
- Pending Approval
- Sent Quotations
- Awarded Quotes
- Quote reports

## Project Engineer Dashboard

Focus on:

- Assigned Leads
- My Quotations
- My Projects
- Cash in Hand
- Cash Expense
- Card Expense
- Transfers
- Pending actions

## Accountant Dashboard

Focus on:

- Awarded Quotes
- Projects Awaiting Activation
- Pending Transactions
- Expense Approvals
- Invoices
- Payments
- Zoho Sync
- Financial Reports

---

# 11. ROLE PERMISSIONS

Implement the client-defined four roles.

## Super Admin

Complete access to:

- All modules
- Configuration
- Settings
- Users
- Roles
- Branches
- Reports
- Projects
- Finance
- Integrations

## Estimation User

Primary access:

- Lead Management
- Estimation
- Quotations
- Quote approval
- Quote reporting
- Relevant project tracking

## Project Engineer

Primary access:

- Assigned Leads
- Quote Preparation
- Assigned Projects
- Expenses
- Cash in Hand
- Card Expenses
- Transfers
- Reports

## Accountant

Primary access:

- Awarded Quotes
- Project Activation
- PO
- Transaction review
- Expense approval
- Zoho Books
- Invoices
- Payments
- Financial reports
- Project closure

Do not expose unauthorized modules.

---

# 12. BRANCH SYSTEM

Branch is a core architecture feature.

Support multiple branches.

Each relevant record must carry branch context.

Examples:

- Lead
- Quote
- Project
- Expense
- Transaction
- Invoice
- Payment
- Reports

Branch-specific quotation formats must be supported.

Example:

Branch 1 → Quote Format 1
Branch 2 → Quote Format 2
Branch 3 → Quote Format 3

Do not hard-code branch IDs.

Users must only access authorized branches.

---

# 13. LEAD MANAGEMENT

Create:

- Lead List
- Lead Creation
- Lead Details
- Lead Assignment
- Lead Activity
- Lead Documents
- Lead Quote

Lead fields should include appropriate client-defined information such as:

- Lead number
- Customer/company
- Project subject
- Branch
- Assigned engineer
- Source
- Date
- Status
- Notes
- Attachments

Required actions:

- Create
- Edit
- Assign
- View
- Create quotation
- Close where permitted

---

# 14. QUOTATION MODULE

Create:

- Quote List
- Quote Creation
- Quote Editor
- Quote Preview
- Quote Approval
- Quote History
- Quote Reports

Quote lifecycle:

Draft
→ Sent
→ Awarded

Support appropriate intermediate workflow states such as approval/revision only where required by the workflow.

---

# 15. QUOTE CREATION EXPERIENCE

The quote editor must be a serious ERP workspace.

Include:

- Customer details
- Project details
- Branch
- Quote number
- Date
- Validity
- Line items
- Quantity
- Unit price
- Tax
- Total
- Technical description
- Terms
- Attachments
- Notes

Actions:

- Save Draft
- Auto Save
- Preview
- Clone
- Submit for Approval
- Send
- Download
- Print

---

# 16. AUTO-SAVE

The client requires draft auto-save.

Display:

Saving...
Saved
Last saved 10:42 AM

If the application crashes/reloads:

Offer:

Restore unsaved draft

Do not lose quote data.

---

# 17. QUOTE CLONE

Provide:

Clone Quote

Also support line-item copy where required.

Cloning must create a new draft.

Never overwrite the original quotation.

---

# 18. MULTI-WINDOW QUOTE WORK

The client requires multi-window provision.

Allow users to:

- Minimize a quote
- Open another quote/lead
- Switch between open working items
- Return to the previous draft

Use a lightweight workspace/tab system.

---

# 19. QUOTE OUTPUTS

Provide three output modes where required:

1. Quote with Total + Tax
2. Quote with Total without Tax
3. Technical Quote without pricing

Actions:

- Preview
- Print
- Download
- Share

Use the appropriate branch-specific quotation format.

---

# 20. QUOTE APPROVAL

Approval page should show:

- Quote details
- Customer
- Project
- Branch
- Engineer
- Amount
- Tax
- Documents
- History

Actions:

- Approve
- Reject
- Request Revision

Always show confirmation before final approval/rejection.

IMPORTANT:
The client document contains a role wording difference between some sections regarding whether the approver is the Estimation User or Admin. Do not silently change the requirement. Make the approval target configurable until the client confirms the final approval role.

---

# 21. PROJECT ACTIVATION

Awarded Quote:

→ Accountant

Accountant enters:

- PO Number
- PO Date
- PO details
- PO document

Then:

Awarded
→ PO Received
→ Project Opened

Opening the project should trigger the required Zoho project/status synchronization.

---

# 22. PROJECT MANAGEMENT

Create:

- Project List
- Project Details
- Project Documents
- Project Expenses
- Project Transactions
- Work Completion
- Invoice
- Closure

Project status:

Awarded
PO Pending
Open
In Progress
Work Completed
Invoice Pending
Closed

---

# 23. ENGINEER EXPENSE RESTRICTION

A Project Engineer can record expenses only against an officially opened project.

Do not allow expense creation against:

- Draft Quote
- Unawarded Quote
- Closed Lead
- Project not yet opened

The UI should make unavailable projects clearly disabled or unavailable.

---

# 24. EXPENSE MODULE

Create four main financial sections:

1. Cash in Hand
2. Total Cash Expense
3. Total Other Expense / Card
4. Total Transfers

Each section must have:

- KPI
- History
- Filters
- Details
- Export
- Print/Download where required

---

# 25. CASH IN HAND

Show:

- Current balance
- Incoming cash
- Expenses
- Transfers
- Running balance

History must support:

- Weekly
- Monthly
- Yearly
- Custom date range

Customer payments received by engineers must be visible to Accountant.

Each engineer may be mapped to a designated Zoho Books petty cash account.

---

# 26. CASH EXPENSE

Show:

- Amount
- Date
- Vendor
- Project
- Category
- Receipt
- User
- Status

Actions:

- Add Expense
- View
- Submit
- Export

---

# 27. CARD EXPENSE

Show:

- Card
- Vendor
- Amount
- Date
- Project
- Receipt
- Status

Allow granular transaction inspection.

---

# 28. INTERNAL TRANSFERS

Transfer workflow:

Create Transfer
→ Pending Approval
→ Approved
→ Cash in Hand credited

After submission:

Normal users MUST NOT be able to edit the transfer.

Only Accountant has edit privileges according to the client requirement.

Make the immutable state visually obvious.

---

# 29. OCR RECEIPT SCANNING

Create:

Scan / Upload Receipt
→ OCR Processing
→ Extracted Data
→ Review
→ Submit
→ Accountant Approval

OCR should extract where possible:

- Vendor
- Amount
- Date
- Receipt reference
- Tax

The user must be able to review and correct extracted values before submission.

---

# 30. ACCOUNTANT APPROVAL CENTER

Create a central approval screen.

Tabs:

- Pending
- Approved
- Rejected
- Cleared

Review:

- User
- Branch
- Project
- Transaction type
- Amount
- Receipt
- Date

Actions:

Approve
Reject
Review

After approval:

Cleared Transaction
→ Zoho Books

---

# 31. ZOHO BOOKS

Zoho integration is a critical requirement.

The client requires two-way integration for:

- Petty Cash Accounts
- Project Creation
- Status Syncing

Create:

- Zoho Dashboard
- Connection Status
- Sync Queue
- Sync Logs
- Failed Sync
- Retry

Never hide sync failures.

---

# 32. WORK COMPLETION

Estimation/admin side prepares the Work Completion Report.

Show:

- Project
- Customer
- Completion information
- Documents
- Notes
- Prepared by
- Date

Submit:

Work Completion Report
→ Accountant Notification
→ Final Invoice

---

# 33. INVOICING

Accountant receives the completion notification.

Workflow:

Work Completed
→ Invoice Required
→ Invoice Created
→ Invoice Submitted
→ Project Closed

Once the final invoice is submitted, project status becomes Closed according to the client requirement.

---

# 34. REPORTS

Create reports for:

- Leads
- Quotes
- Projects
- Cash transactions
- Card transactions
- Internal transfers
- Combined cash in/out
- Financial data
- Branch-wise performance
- Operational data

Required expense reports:

1. Total Cash Transaction Report
2. Total Card Transaction Report
3. Total Internal Cash In & Out Report
4. Combined Total Cash In & Cash Out Report

Support:

- Filter
- Print
- Download
- Share

---

# 35. GLOBAL SEARCH

Search should work across the ERP.

Search examples:

- PO Number
- Quote Number
- Project Subject
- Engineer
- Branch
- Customer
- Lead
- Project
- Invoice
- Transaction

The search should return categorized results.

---

# 36. NOTIFICATIONS

Notifications should be connected to real workflow events.

Examples:

- New lead assigned
- Quote submitted
- Quote approved
- Quote rejected
- Quote awarded
- Project opened
- Expense submitted
- Expense approved/rejected
- Transfer awaiting approval
- Work completion submitted
- Invoice required
- Zoho sync failed

---

# 37. TABLE STANDARD

All tables must follow the same design system.

Rules:

- Approximately 5–6 visible rows
- Vertical internal scrolling after that
- Sticky table header
- Horizontal scrolling when necessary
- Header/body column alignment
- Search/filter above table
- Pagination below where required
- Responsive behavior

Do not allow long tables to stretch the entire page.

---

# 38. POPUPS / DRAWERS / MODALS

Use popups only where they improve productivity.

Use modal/drawer for:

- Quick Add Lead
- Assign Engineer
- Quick Expense
- Approval confirmation
- Small edits
- Confirmation
- OCR review

Use full-page/workspace screens for:

- Quote creation
- Project details
- Complex expense review
- Reports

Do not put large workflows inside tiny popups.

---

# 39. CLICKABLE DEMO REQUIREMENT

Before backend development begins, create a realistic clickable demo.

The demo must NOT be a collection of static screenshots.

Every important visible interaction should work.

Minimum clickable actions:

### Dashboard

- Sidebar navigation
- Global search
- Branch selector
- Filters
- KPI cards
- Charts period filters
- Lead Pipeline cards
- Recent Leads
- Upcoming Activities
- Quick Actions
- Notifications
- Profile menu

### Lead

- Add Lead
- Edit
- Assign Engineer
- View
- Create Quote

### Quote

- Open
- Edit
- Save Draft
- Auto-save indication
- Clone
- Preview
- Submit for Approval
- Approve
- Reject
- Send
- Download/Print placeholder action

### Project

- Open Project
- Add PO
- View documents
- Work Completion
- Invoice

### Expenses

- Add Expense
- Scan Receipt
- View Cash in Hand
- Transfer
- Submit
- Approve/Reject

### Accountant

- Open approval
- Approve
- Reject
- View cleared transaction
- Open Zoho status

---

# 40. DEMO DATA

Use realistic demo data only for the clickable prototype.

Do NOT connect demo data directly to production.

Clearly structure demo data so it can later be replaced by API/database data.

Example:

Leads:
- Al Futtaim Group
- Emirates Build
- Gulf Infrastructure
- Desert Tech
- Modern Contracts

Quotes:
- Quote numbers
- Customer
- Project
- Branch
- Engineer
- Amount
- Status

Projects:
- Dubai Mall Extension
- Abu Dhabi Tower
- Sharjah Warehouse
- Ajman Residence
- Ras Al Khaimah Port

Expenses:
- Cash
- Card
- Transfer

Use AED for UAE financial examples.

---

# 41. DEMO SHOULD FEEL LIKE A REAL PRODUCT

The user should be able to navigate:

Login
→ Dashboard
→ Lead
→ Quote
→ Approval
→ Awarded
→ Project
→ Expense
→ Accountant Approval
→ Zoho
→ Invoice
→ Closure

Do not show dead buttons.

If an action cannot yet be connected to a real backend, make the prototype interaction demonstrate the intended result and clearly keep the code/data layer replaceable.

---

# 42. BACKEND-READY ARCHITECTURE

The clickable demo must be created in a way that does not need to be thrown away when backend development begins.

Use:

- Reusable components
- Reusable tables
- Reusable forms
- Centralized status definitions
- Centralized role definitions
- Centralized navigation
- API-ready services
- Typed models/interfaces where supported
- Separate UI state from business data
- Mock/demo data in a separate layer

Do NOT put business logic directly into presentation components.

---

# 43. DATA CONTRACTS

For each module define the expected data shape.

Examples:

Lead:

```text
id
leadNumber
customer
projectSubject
branchId
assignedEngineerId
source
status
createdAt
documents
```

Quote:

```text
id
quoteNumber
leadId
branchId
engineerId
status
subtotal
tax
total
items
documents
```

Project:

```text
id
projectNumber
quoteId
poNumber
branchId
engineerId
status
documents
```

Expense:

```text
id
projectId
userId
branchId
type
amount
date
vendor
receipt
status
```

Keep these models ready to connect to Laravel APIs later.

---

# 44. BACKEND DEVELOPMENT PREPARATION

Do not start database/backend implementation until:

1. Client document has been fully understood.
2. UI structure is approved.
3. Clickable demo is tested.
4. Role permissions are validated.
5. Main workflow is validated.
6. Module list is confirmed.

After demo approval, backend development can begin using the same:

- Module names
- Routes
- Data models
- Statuses
- Roles
- Permissions
- Forms
- API contracts

This prevents redesign/redevelopment.

---

# 45. SECURITY

Design and implementation must support:

- Role-based access control
- Branch access control
- Secure authentication
- Encrypted API communication
- Secure file uploads
- Audit logs
- Approval restrictions
- Immutable submitted transfers
- Secure Zoho integration
- No unauthorized access

The client explicitly requires cloud/mobile/API security and encryption.

---

# 46. MOBILE APPLICATION

The client requires Android and iOS availability.

The Project Engineer mobile experience should prioritize:

- Assigned work
- Lead details
- Quote preparation
- Quote drafts
- Documents
- Expenses
- Cash in Hand
- Transfers
- Notifications

Do not simply shrink the desktop dashboard to mobile.

Create a proper mobile workflow.

---

# 47. DESIGN QA

For every screen check:

- Pixel alignment
- Spacing
- Typography
- Card dimensions
- Button dimensions
- Icon alignment
- Table row height
- Form alignment
- Sidebar consistency
- Header consistency
- Responsive behavior
- Overflow
- Scroll
- Sticky headers
- Empty states
- Loading states
- Error states
- Hover/focus states
- Permission visibility

All modules must look like one product.

---

# 48. DEMO ACCEPTANCE CHECKLIST

Before saying the demo is complete:

- [ ] Login works
- [ ] Role switching/demo roles work
- [ ] Sidebar works
- [ ] Dashboard works
- [ ] Filters work
- [ ] KPI cards work
- [ ] Lead Pipeline works
- [ ] Lead creation works
- [ ] Lead assignment works
- [ ] Quote creation works
- [ ] Quote draft works
- [ ] Auto-save is demonstrated
- [ ] Quote clone works
- [ ] Quote approval works
- [ ] Awarded flow works
- [ ] Project creation works
- [ ] PO upload is demonstrated
- [ ] Project status changes work
- [ ] Expense creation works
- [ ] OCR flow is demonstrated
- [ ] Transfer approval works
- [ ] Accountant approval works
- [ ] Zoho sync status is demonstrated
- [ ] Work completion works
- [ ] Invoice flow works
- [ ] Project closure works
- [ ] Reports work
- [ ] Search works
- [ ] Notifications work
- [ ] Responsive layout works
- [ ] Tables scroll correctly
- [ ] No major dead buttons
- [ ] No critical console errors

---

# 49. IMPORTANT: DO NOT CHANGE APPROVED DESIGN WITHOUT REASON

The current approved Rich Mount ERP design should be preserved.

If a functional requirement requires a new screen:

Create it using the existing design system.

If a new module is required:

Use the same:

- Sidebar
- Header
- Colors
- Typography
- Cards
- Tables
- Forms
- Status badges
- Buttons
- Modal patterns

Do not introduce a completely different visual language.

---

# 50. FINAL WORKFLOW FOR THE AI CODING/DESIGN AGENT

Follow this exact sequence:

## STEP 1 — REQUIREMENT AUDIT

Read the entire client PDF.

Produce:

- Requirements summary
- Role matrix
- Module list
- Workflow map
- Branch rules
- Approval rules
- Integration requirements
- Ambiguities

Do not code yet.

## STEP 2 — DESIGN AUDIT

Inspect the existing approved design.

Identify:

- Layout
- Navigation
- Colors
- Components
- Tables
- Forms
- Dashboard
- Lead Pipeline
- Charts
- Cards
- Responsive patterns

Do not redesign it.

## STEP 3 — INFORMATION ARCHITECTURE

Create:

- Sitemap
- Role navigation
- Permission matrix
- Page inventory
- User journeys

## STEP 4 — DESIGN SYSTEM

Create:

- Color tokens
- Typography
- Spacing
- Components
- Status system
- Table system
- Form system
- Modal system

## STEP 5 — CLICKABLE DEMO

Implement all important navigation and workflows using realistic demo data.

## STEP 6 — DEMO QA

Test every workflow end-to-end.

## STEP 7 — CLIENT REVIEW

Prepare the demo for client review.

Do not start irreversible backend/database changes before the demo structure is accepted.

## STEP 8 — BACKEND PREPARATION

After demo approval:

- Finalize data models
- Finalize API contracts
- Finalize database schema
- Finalize role permissions
- Finalize branch architecture

## STEP 9 — BACKEND DEVELOPMENT

Implement the production backend and database.

## STEP 10 — FRONTEND/API INTEGRATION

Replace demo data with real APIs without changing the approved UI.

## STEP 11 — FINAL QA

Test:

- Roles
- Permissions
- Branch isolation
- Financial calculations
- Approvals
- Zoho sync
- OCR
- Reports
- Mobile
- Security
- Performance

---

# 51. FINAL PRINCIPLE

The project must follow this principle:

CLIENT DOCUMENT
↓
REQUIREMENTS AUDIT
↓
INFORMATION ARCHITECTURE
↓
APPROVED DESIGN SYSTEM
↓
HIGH-FIDELITY UI
↓
CLICKABLE DEMO
↓
CLIENT VALIDATION
↓
DATABASE + API DESIGN
↓
LARAVEL BACKEND
↓
FRONTEND/API INTEGRATION
↓
TESTING
↓
PRODUCTION ERP

Do not skip the clickable-demo validation stage.

Do not build a backend based on assumptions that have not been represented in the approved workflow.

Do not throw away the approved UI when backend development begins.

The final product must be a single consistent Rich Mount ERP system where the client requirements, UI/UX, clickable demo, database, backend, APIs, mobile application and integrations all follow the same approved business workflow.
