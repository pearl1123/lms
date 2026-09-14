# Weekly Accomplishment Report

**Name:** Ivan Barrac  
**Period:** August 24 – September 2, 2026  
**System:** kaBAGA Academy LMS  

| Date | Accomplished (Based on the target activities) |
|------|--------------------------------------------------|
| August 24, 2026 | Reviewed instructor and admin access in User Management. Fixed cases where a user was in the Instructor group but the account role did not update (e.g. still showed as employee). |
| August 24, 2026 | Improved group-based permission handling so role changes apply correctly when users are added to or removed from access groups. |
| August 25, 2026 | Built **Learning Mode** so Admin, Teacher, and Instructor can switch to the learner experience (enroll, take courses, earn certificates) without using a separate account. |
| August 25, 2026 | Added Learning Mode toggle in the sidebar with a clear visual indicator (navbar pill and sidebar theme) when active. |
| August 25, 2026 | Blocked management screens while in Learning Mode (Manage Courses, Reports, Users, Settings, Permissions, Libraries, and related staff-only actions). |
| August 26, 2026 | Fixed assessment behavior in Learning Mode so take, retake, and result pages use the learner experience instead of the instructor/manager UI. |
| August 26, 2026 | Fixed certificate and catalog views so staff-only actions do not appear when the user is acting as a learner. |
| August 26, 2026 | Fixed permission error when toggling Learning Mode (profile access and employee manifest fallback). |
| August 27, 2026 | Restricted **Create Course** modality dropdown so only **Asynchronous** can be selected; Face-to-Face, Online Live, and HyFlex show as **Coming Soon**. |
| August 27, 2026 | Added server-side validation on create course so unavailable modalities cannot be submitted. |
| August 27, 2026 | Added **Training duration (hours)** on course Overview for use on certificates. Created database migration `migration_course_training_hours.sql`. |
| August 27, 2026 | Improved course edit and create screens with a **sticky Save bar** and header **Save** button so staff do not need to scroll back to the top on long forms. |
| August 27, 2026 | Fixed overlap of the save bar with the **Danger Zone** sidebar and the page footer copyright line. |
| September 1, 2026 | Investigated Overview fields not saving (**Pass threshold %**, **Sequential modules**, **Enrollment deadline**). Found missing database columns on the `courses` table. |
| September 1, 2026 | Created and applied migration `migration_course_overview_fields.sql` for pass threshold, sequential modules, enrollment deadline, and max capacity. |
| September 1, 2026 | Fixed course save logic so Overview fields are written to the database. Pass threshold now applies correctly during assessments (not only the platform default 75%). |
| September 1, 2026 | Removed the rule that blocked **Overview** save when module weights were not 100%. Module weight total is now required only when **publishing** a course that has modules. |
| September 2, 2026 | Locked **Meeting Link** in Add Module as **Coming Soon** (greyed out, not selectable for new modules). Existing Meeting Link modules can still be edited. |
| September 2, 2026 | Added server-side block so new Meeting Link modules cannot be created until the feature is ready. |
| September 2, 2026 | Relaxed form validation so blank **Training hours** and **Pass threshold** no longer block saving other Overview details. |
| September 2, 2026 | Prepared ETD **User Acceptance** checklist, **Employee/Instructor user guides**, **Create Course guide**, and **IPCR module list** for kaBAGA Academy LMS documentation and sign-off. |

---

**Note:** This covers only the work of Ivan Barrac. Work by other developers is not included. Some items (Learning Mode, Overview field migration, sticky save bar, Meeting Link lock) are implemented in the system and ready for review. Run `application/sql/migration_course_overview_fields.sql` and `application/sql/migration_course_training_hours.sql` on any environment where those columns are not yet applied.
