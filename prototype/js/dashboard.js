// Global variables
let currentUser = null;
let currentView = "overview";

// Initialize dashboard
async function initDashboard() {
    try {
        const res = await fetch("/prototype/backend/dashboard.php");
        const data = await res.json();

        if (!data.authenticated) {
            window.location.href = "/prototype/index.html#portal";
            return;
        }

        currentUser = data.user;
        document.getElementById("navUserName").textContent = data.user.user_name;

        buildSidebar(data.user.role_id);
        loadView("overview");
    } catch (err) {
        showError("Failed to load dashboard");
    }
}

// Build sidebar based on role
function buildSidebar(roleId) {
    const menu = document.getElementById("sidebarMenu");

    const studentMenu = [
        { id: "overview", icon: "house-door", label: "Overview" },
        { id: "my-courses", icon: "book", label: "My Courses" },
        { id: "assignments", icon: "clipboard-check", label: "Assignments" },
        { id: "grades", icon: "trophy", label: "Grades" },
    ];

    const teacherMenu = [
        { id: "overview", icon: "house-door", label: "Overview" },
        { id: "manage-courses", icon: "folder", label: "Manage Courses" },
        { id: "view-submissions", icon: "inbox", label: "View Submissions" },
        { id: "grade-students", icon: "pencil-square", label: "Grade Students" },
    ];

    const menuItems = roleId === 1 ? studentMenu : teacherMenu;

    menu.innerHTML = menuItems
        .map(
            (item) => `
        <li class="nav-item">
            <a class="nav-link ${item.id === "overview" ? "active" : ""}" href="#" data-view="${item.id}">
                <i class="bi bi-${item.icon} me-2"></i>
                ${item.label}
            </a>
        </li>
    `,
        )
        .join("");

    // Add click handlers
    menu.querySelectorAll("a[data-view]").forEach((link) => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            const view = e.currentTarget.dataset.view;

            // Update active state
            menu
                .querySelectorAll(".nav-link")
                .forEach((l) => l.classList.remove("active"));
            e.currentTarget.classList.add("active");

            loadView(view);
        });
    });
}

// Load different views
async function loadView(view) {
    currentView = view;
    const content = document.getElementById("mainContent");
    const title = document.getElementById("pageTitle");

    content.innerHTML =
        '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';

    try {
        switch (view) {
            case "overview":
                title.textContent = "Dashboard Overview";
                await loadOverview();
                break;
            case "my-courses":
                title.textContent = "My Courses";
                await loadStudentCourses();
                break;
            case "assignments":
                title.textContent = "My Assignments";
                await loadStudentAssignments();
                break;
            case "grades":
                title.textContent = "My Grades";
                await loadStudentGrades();
                break;
            case "manage-courses":
                title.textContent = "Manage Courses";
                await loadTeacherCourses();
                break;
            case "view-submissions":
                title.textContent = "View Submissions";
                await loadTeacherSubmissions("view");
                break;

            case "grade-students":
                title.textContent = "Grade Students";
                await loadTeacherSubmissions("grade");
                break;

            default:
                content.innerHTML =
                    '<div class="alert alert-warning">View not found</div>';
        }
    } catch (err) {
        content.innerHTML = `<div class="alert alert-danger">Error loading content: ${err.message}</div>`;
    }
}

// Overview view
async function loadOverview() {
    const content = document.getElementById("mainContent");

    if (currentUser.role_id === 1) {
        // Student overview
        const res = await fetch(
            "/prototype/backend/std_endpoints.php?action=overview",
        );
        const data = await res.json();

        content.innerHTML = `
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-book fs-1 text-primary"></i>
                            <h3 class="mt-3">${data.total_courses || 0}</h3>
                            <p class="text-muted">Enrolled Courses</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-clipboard-check fs-1 text-success"></i>
                            <h3 class="mt-3">${data.pending_assignments || 0}</h3>
                            <p class="text-muted">Pending Assignments</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-trophy fs-1 text-warning"></i>
                            <h3 class="mt-3">${data.average_grade || "N/A"}</h3>
                            <p class="text-muted">Average Grade</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <h4>Recent Activity</h4>
                <div class="list-group">
                    ${data.recent_activity?.length
                ? data.recent_activity
                    .map(
                        (act) => `
                        <div class="list-group-item">
                            <i class="bi bi-${act.icon} me-2"></i>
                            ${act.message}
                            <small class="text-muted float-end">${act.time}</small>
                        </div>
                    `,
                    )
                    .join("")
                : '<div class="alert alert-info">No recent activity</div>'
            }
                </div>
            </div>
        `;
    } else {
        // Teacher overview
        const res = await fetch(
            "/prototype/backend/tch_endpoints.php?action=overview",
        );
        const data = await res.json();

        content.innerHTML = `
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-folder fs-1 text-primary"></i>
                            <h3 class="mt-3">${data.total_courses || 0}</h3>
                            <p class="text-muted">My Courses</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-inbox fs-1 text-info"></i>
                            <h3 class="mt-3">${data.pending_grading || 0}</h3>
                            <p class="text-muted">Pending Grading</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-people fs-1 text-success"></i>
                            <h3 class="mt-3">${data.total_students || 0}</h3>
                            <p class="text-muted">Total Students</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
}

// Student: Load courses
async function loadStudentCourses() {
    const res = await fetch(
        "/prototype/backend/std_endpoints.php?action=courses",
    );
    const data = await res.json();
    const content = document.getElementById("mainContent");
    const title = document.getElementById("pageTitle");

    title.textContent = "My Courses";

    if (!data.success) {
        content.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        return;
    }

    // No enrolled courses
    if (!data.courses || data.courses.length === 0) {
        content.innerHTML = `
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <span>You are not enrolled in any courses yet.</span>
                <button class="btn btn-primary btn-sm"
                    onclick="loadAvailableCourses()">
                    <i class="bi bi-plus-circle me-1"></i>
                    Enroll in Courses
                </button>
            </div>
        `;
        return;
    }

    // Enrolled courses list
    content.innerHTML = `
        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-outline-primary btn-sm"
                onclick="loadAvailableCourses()">
                <i class="bi bi-plus-circle me-1"></i>
                Enroll in more courses
            </button>
        </div>

        <div class="row g-4">
            ${data.courses
            .map(
                (course) => `
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">
                                ${escapeHtml(course.course_code)}
                            </h5>

                            <h6 class="card-subtitle mb-2 text-muted">
                                ${escapeHtml(course.course_name)}
                            </h6>

                            <p class="card-text flex-grow-1">
                                ${escapeHtml(course.description || "No description")}
                            </p>

                            <p class="text-muted small mb-3">
                                <i class="bi bi-person me-1"></i>
                                ${escapeHtml(course.teacher_name)}<br>
                                <i class="bi bi-calendar me-1"></i>
                                ${escapeHtml(course.semester || "N/A")}
                            </p>

                            <button class="btn btn-sm btn-outline-danger mt-auto"
                                onclick="unenrollCourse(${course.id})">
                                <i class="bi bi-x-circle me-1"></i>
                                Unenroll
                            </button>
                        </div>
                    </div>
                </div>
            `,
            )
            .join("")}
        </div>
    `;
}

async function unenrollCourse(courseId) {
    if (!confirm("Are you sure you want to unenroll from this course?")) return;

    const formData = new FormData();
    formData.append("subject_id", courseId);

    const res = await fetch(
        "/prototype/backend/std_endpoints.php?action=unenroll",
        {
            method: "POST",
            body: formData,
        },
    );

    const data = await res.json();

    if (data.success) {
        showSuccess("You have been unenrolled.");
        loadView("my-courses");
    } else {
        showError(data.error || "Failed to unenroll");
    }
}

async function loadAvailableCourses() {
    const res = await fetch(
        "/prototype/backend/std_endpoints.php?action=available_courses",
    );
    const data = await res.json();
    const content = document.getElementById("mainContent");
    const title = document.getElementById("pageTitle");

    title.textContent = "Available Courses";

    content.innerHTML = `
        <div class="row g-4">
            ${data.courses.length
            ? data.courses
                .map(
                    (course) => `
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5>${escapeHtml(course.sub_code)}</h5>
                            <h6 class="text-muted">${escapeHtml(course.sub_title)}</h6>
                            <p>${escapeHtml(course.sub_description || "No description")}</p>
                            <p class="small text-muted">
                                Teacher: ${escapeHtml(course.teacher_name)}<br>
                                Semester: ${escapeHtml(course.sub_semester || "N/A")}
                            </p>
                            <button class="btn btn-success btn-sm"
                                onclick="enrollCourse(${course.id})">
                                Enroll
                            </button>
                        </div>
                    </div>
                </div>
            `,
                )
                .join("")
            : `
                <div class="col-12">
                    <div class="alert alert-info">No available courses</div>
                </div>
            `
        }
        </div>
    `;
}

async function enrollCourse(courseId) {
    if (!confirm("Enroll in this course?")) return;

    const formData = new FormData();
    formData.append("subject_id", courseId);

    const res = await fetch(
        "/prototype/backend/std_endpoints.php?action=enroll",
        {
            method: "POST",
            body: formData,
        },
    );

    const data = await res.json();

    if (data.success) {
        showSuccess("Enrolled successfully!");
        loadView("my-courses");
    } else {
        showError(data.error || "Enrollment failed");
    }
}

// Student: Load assignments
async function loadStudentAssignments() {
    const res = await fetch(
        "/prototype/backend/std_endpoints.php?action=assignments",
    );
    const data = await res.json();
    const content = document.getElementById("mainContent");

    if (!data.success) {
        content.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        return;
    }

    content.innerHTML = `
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Course</th>
            <th>Assignment</th>
            <th>Due Date</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          ${data.assignments?.length
            ? data.assignments
                .map(
                    (a) => `
              <tr>
                <td>${escapeHtml(a.sub_code)}</td>
                <td>${escapeHtml(a.title)}</td>
                <td>${formatDateTime(a.due_date)}</td>
                <td>
                  ${a.submitted
                            ? '<span class="badge bg-success">Submitted</span>'
                            : '<span class="badge bg-warning">Pending</span>'
                        }
                </td>
                <td>
                  ${!a.submitted
                            ? `<button class="btn btn-sm btn-primary"
                            onclick="openSubmitModal(${a.id})">
                          Submit
                        </button>`
                            : a.grade !== null
                                ? '<span class="text-success fw-semibold">Graded</span>'
                                : '<span class="text-muted">Awaiting grade</span>'
                        }
                </td>
              </tr>
            `,
                )
                .join("")
            : `<tr>
                   <td colspan="5" class="text-center">
                     No assignments found
                   </td>
                 </tr>`
        }
        </tbody>
      </table>
    </div>
  `;
}

// Student: Load grades
async function loadStudentGrades() {
    const res = await fetch("/prototype/backend/std_endpoints.php?action=grades");
    const data = await res.json();
    const content = document.getElementById("mainContent");

    if (!data.success) {
        content.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        return;
    }

    content.innerHTML = `
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Course</th>
            <th>Assignment</th>
            <th>Grade</th>
            <th>Feedback</th>
            <th>Graded On</th>
          </tr>
        </thead>
        <tbody>
          ${data.grades?.length
            ? data.grades
                .map(
                    (g) => `
              <tr>
                <td>${escapeHtml(g.sub_code)}</td>
                <td>${escapeHtml(g.assignment_title)}</td>
                <td>
                  <span class="badge bg-${getScoreBadgeColor(g.grade)}">
                    ${g.grade}
                  </span>
                </td>
                <td>${escapeHtml(g.feedback || "-")}</td>
                <td>${formatDateTime(g.submitted_at)}</td>
              </tr>
            `,
                )
                .join("")
            : `<tr>
                   <td colspan="5" class="text-center">
                     No grades yet
                   </td>
                 </tr>`
        }
        </tbody>
      </table>
    </div>
  `;
}

// Teacher: Load courses
async function loadTeacherCourses() {
    const res = await fetch(
        "/prototype/backend/tch_endpoints.php?action=subjects_catalog"
    );
    const data = await res.json();
    const content = document.getElementById("mainContent");

    if (!data.success) {
        content.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        return;
    }

    content.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="alert alert-secondary mb-0 flex-grow-1 me-3">
                <strong>All Subjects Catalog:</strong>
                You can see all available subjects.
                Click <em>Teach</em> to assign yourself for a semester.
                You can manage only the subjects you teach.
            </div>

            <button class="btn btn-success"
                onclick="openCreateCourseModal()">
                <i class="bi bi-plus-circle me-1"></i>
                Create Course
            </button>
        </div>

        <div class="row g-4">
            ${data.subjects?.length
            ? data.subjects.map((s) => `
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">${escapeHtml(s.sub_code)}</h5>
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        ${escapeHtml(s.sub_title)}
                                    </h6>

                                    <p class="card-text">
                                        ${escapeHtml(s.sub_description || "No description")}
                                    </p>

                                    <p class="text-muted small mb-3">
                                        <i class="bi bi-calendar me-1"></i>
                                        ${escapeHtml(s.sub_semester || "N/A")}<br>

                                        <i class="bi bi-people me-1"></i>
                                        ${s.student_count || 0} students

                                        ${s.is_teaching == 1
                    ? `<br>
                                                   <i class="bi bi-check-circle me-1"></i>
                                                   Teaching: ${escapeHtml(s.teaching_semester || "")}`
                    : ""
                }
                                    </p>

                                    <div class="d-flex gap-2 flex-wrap">
                                        ${s.is_teaching == 1
                    ? `
                                                    <button class="btn btn-sm btn-outline-primary"
                                                        onclick="viewCourseAssignments(${s.id})">
                                                        Assignments
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger"
                                                        onclick="leaveSubject(${s.id})">
                                                        Leave
                                                    </button>
                                                  `
                    : `
                                                    <button class="btn btn-sm btn-primary"
                                                        onclick="openTakeSubjectModal(${s.id})">
                                                        Teach
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary" disabled>
                                                        Assignments
                                                    </button>
                                                  `
                }
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join("")
            : `
                        <div class="col-12">
                            <div class="alert alert-info">No subjects found.</div>
                        </div>
                    `
        }
        </div>
    `;
}


function openCreateCourseModal() {
    document.getElementById("courseForm").reset();
    document.getElementById("courseModalTitle").textContent = "Create Course";

    new bootstrap.Modal(
        document.getElementById("courseModal")
    ).show();
}


async function editCourse(courseId) {
    const res = await fetch(
        `/prototype/backend/tch_endpoints.php?action=get_course&course_id=${courseId}`,
    );
    const data = await res.json();

    if (!data.success) {
        showError(data.error);
        return;
    }

    const c = data.course;

    document.getElementById("courseModalTitle").textContent = "Edit Course";
    document.getElementById("courseId").value = c.id;
    document.getElementById("courseCode").value = c.sub_code;
    document.getElementById("courseName").value = c.sub_title;
    document.getElementById("courseDescription").value = c.sub_description || "";
    document.getElementById("courseSemester").value = c.sub_semester || "";

    new bootstrap.Modal(document.getElementById("courseModal")).show();
}

// Teacher: View course assignments
async function viewCourseAssignments(courseId) {
    const res = await fetch(
        `/prototype/backend/tch_endpoints.php?action=course_assignments&course_id=${courseId}`,
    );
    const data = await res.json();
    const content = document.getElementById("mainContent");

    content.innerHTML = `
        <button class="btn btn-secondary mb-3"
            onclick="loadView('manage-courses')">
            <i class="bi bi-arrow-left me-1"></i> Back to Courses
        </button>

        <button class="btn btn-primary mb-3 ms-2"
            onclick="openAssignmentModal(${courseId})">
            <i class="bi bi-plus-circle me-1"></i> Create Assignment
        </button>

        <h4 class="mb-3">${escapeHtml(data.course_name)} - Assignments</h4>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Due Date</th>
                        <th>Submissions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${data.assignments.length
            ? data.assignments
                .map(
                    (a) => `
                                <tr>
                                    <td>${escapeHtml(a.title)}</td>
                                    <td>${formatDateTime(a.due_date)}</td>
                                    <td>${a.submission_count}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info"
                                            onclick="loadView('view-submissions')">
                                            View Submissions
                                        </button>
                                    </td>
                                </tr>
                            `,
                )
                .join("")
            : `<tr>
                                <td colspan="4" class="text-center">
                                    No assignments yet
                                </td>
                               </tr>`
        }
                </tbody>
            </table>
        </div>
    `;
}

// Teacher: View submissions
async function loadTeacherSubmissions(mode = "view") {
    const res = await fetch(
        "/prototype/backend/tch_endpoints.php?action=submissions",
    );
    const data = await res.json();
    const content = document.getElementById("mainContent");

    content.innerHTML = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Assignment</th>
                        <th>Student</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        ${mode === "grade" ? "<th>Action</th>" : ""}
                    </tr>
                </thead>
                <tbody>
                    ${data.submissions
            .map(
                (s) => `
                        <tr>
                            <td>${escapeHtml(s.sub_code)}</td>
                            <td>${escapeHtml(s.assignment_title)}</td>
                            <td>${escapeHtml(s.student_name)}</td>
                            <td>${formatDateTime(s.submitted_at)}</td>
                            <td>
                                ${s.grade !== null
                        ? `<span class="badge bg-success">Graded (${s.grade})</span>`
                        : `<span class="badge bg-warning">Pending</span>`
                    }
                            </td>
                ${mode === "grade"
                        ? `<td>
        <button class="btn btn-sm btn-primary"
            onclick='openGradeModal(
                ${s.id},
                ${JSON.stringify(s.student_name || "")},
               ${JSON.stringify((s.submission_text || "").replace(/'/g, "\\'").replace(/\n/g, " "))},
                ${s.grade === null ? "null" : Number(s.grade)},
                ${JSON.stringify(s.file_path || "")}
            )'>
            ${s.grade !== null ? "Edit Grade" : "Grade"}
        </button>
      </td>`
                        : ""
                    }

                        </tr>
                    `,
            )
            .join("")}
                </tbody>
            </table>
        </div>
    `;
}

// Teacher: Grade students view
async function loadGradeStudents() {
    const res = await fetch(
        "/prototype/backend/tch_endpoints.php?action=submissions",
    );
    const data = await res.json();

    loadTeacherSubmissions();
}

// Modal functions
function openCourseModal() {
    document.getElementById("courseForm").reset();
    document.getElementById("courseId").value = "";
    document.getElementById("courseModalTitle").textContent = "Create Course";

    new bootstrap.Modal(document.getElementById("courseModal")).show();
}

function openAssignmentModal(courseId) {
    const modal = new bootstrap.Modal(document.getElementById("assignmentModal"));
    document.getElementById("assignmentForm").reset();
    document.getElementById("assignmentCourseId").value = courseId;
    modal.show();
}

function openSubmitModal(assignmentId) {
    const modal = new bootstrap.Modal(document.getElementById("submitModal"));
    document.getElementById("submitForm").reset();
    document.getElementById("submitAssignmentId").value = assignmentId;
    modal.show();
}

function openGradeModal(
    submissionId,
    studentName,
    submissionText,
    currentScore,
    filePath
) {
    const modalEl = document.getElementById("gradeModal");
    const modal = new bootstrap.Modal(modalEl);

    modalEl.querySelector("form").reset();

    modal.show();

    //wait for modal to fully open
    setTimeout(() => {
        document.getElementById("gradeSubmissionId").value = submissionId;
        document.getElementById("gradeStudentName").textContent = studentName;

        document.getElementById("gradeSubmissionText").textContent =
            submissionText || "No text submission";

        document.getElementById("gradeScore").value =
            currentScore !== null ? currentScore : "";

        const pdfLink = document.getElementById("gradePdfLink");
        if (filePath) {
            pdfLink.href = `/prototype/backend/${filePath}`;
            pdfLink.style.display = "inline-block";
        } else {
            pdfLink.style.display = "none";
        }
    }, 150);
}



function openTakeSubjectModal(subjectId) {
    document.getElementById("takeSubjectForm").reset();
    document.getElementById("takeSubjectId").value = subjectId;

    new bootstrap.Modal(document.getElementById("takeSubjectModal")).show();
}

async function leaveSubject(subjectId) {
    if (!confirm("Are you sure you want to leave this subject?")) return;

    const formData = new FormData();
    formData.append("subject_id", subjectId);

    const res = await fetch(
        "/prototype/backend/tch_endpoints.php?action=leave_subject",
        {
            method: "POST",
            body: formData,
        },
    );

    const data = await res.json();
    if (!data.success) {
        showError(data.error || "Failed to leave subject");
        return;
    }

    showSuccess("You left the subject.");
    loadView("manage-courses");
}

document
    .getElementById("takeSubjectForm")
    ?.addEventListener("submit", async (e) => {
        e.preventDefault();

        const formData = new FormData(e.target);

        const res = await fetch(
            "/prototype/backend/tch_endpoints.php?action=take_subject",
            {
                method: "POST",
                body: formData,
            },
        );

        const data = await res.json();

        if (data.success) {
            bootstrap.Modal.getInstance(
                document.getElementById("takeSubjectModal"),
            ).hide();
            showSuccess("You are now teaching this subject!");
            loadView("manage-courses");
        } else {
            showError(data.error || "Failed to take subject");
        }
    });

// Form submissions
document.getElementById("courseForm")?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);

    try {
        const res = await fetch(
            "/prototype/backend/tch_endpoints.php?action=create_course",
            {
                method: "POST",
                body: formData,
            },
        );
        const data = await res.json();

        if (data.success) {
            bootstrap.Modal.getInstance(
                document.getElementById("courseModal"),
            ).hide();
            showSuccess("Course created successfully!");
            loadView("manage-courses");
        } else {
            showError(data.error || "Failed to create course");
        }
    } catch (err) {
        showError("Server error");
    }
});

document
    .getElementById("assignmentForm")
    ?.addEventListener("submit", async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);

        try {
            const res = await fetch(
                "/prototype/backend/tch_endpoints.php?action=create_assignment",
                {
                    method: "POST",
                    body: formData,
                },
            );
            const data = await res.json();

            if (data.success) {
                bootstrap.Modal.getInstance(
                    document.getElementById("assignmentModal"),
                ).hide();
                showSuccess("Assignment created successfully!");
                const courseId = document.getElementById("assignmentCourseId").value;
                viewCourseAssignments(courseId);
            } else {
                showError(data.error || "Failed to create assignment");
            }
        } catch (err) {
            showError("Server error");
        }
    });

document.getElementById("submitForm")?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);

    try {
        const res = await fetch(
            "/prototype/backend/std_endpoints.php?action=submit_assignment",
            {
                method: "POST",
                body: formData,
            },
        );
        const data = await res.json();

        if (data.success) {
            bootstrap.Modal.getInstance(
                document.getElementById("submitModal"),
            ).hide();
            showSuccess("Assignment submitted successfully!");
            loadView("assignments");
        } else {
            showError(data.error || "Failed to submit assignment");
        }
    } catch (err) {
        showError("Server error");
    }
});
document.addEventListener("submit", async (e) => {
    if (e.target && e.target.id === "gradeForm") {
        e.preventDefault();

        const formData = new FormData(e.target);

        try {
            const res = await fetch(
                "/prototype/backend/tch_endpoints.php?action=grade_submission",
                {
                    method: "POST",
                    body: formData,
                }
            );

            const data = await res.json();

            if (data.success) {
                bootstrap.Modal
                    .getInstance(document.getElementById("gradeModal"))
                    .hide();

                showSuccess("Grade saved successfully!");
                loadView("view-submissions");
            } else {
                showError(data.error || "Failed to save grade");
            }
        } catch (err) {
            showError("Server error while saving grade");
        }

    }
});


// Logout
document.getElementById("logoutBtn").addEventListener("click", async () => {
    await fetch("/prototype/backend/logout.php", { method: "POST" });
    window.location.href = "/prototype/index.html";
});

// Helper functions
function escapeHtml(text) {
    if (!text) return "";
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
}

function formatDateTime(dateStr) {
    if (!dateStr) return "N/A";
    const date = new Date(dateStr);
    return (
        date.toLocaleDateString() +
        " " +
        date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })
    );
}

function getScoreBadgeColor(score) {
    if (score >= 90) return "success";
    if (score >= 70) return "primary";
    if (score >= 50) return "warning";
    return "danger";
}

function showSuccess(message) {
    alert(message);
}

function showError(message) {
    alert("Error: " + message);
}

document.addEventListener("click", (e) => {
    const btn = e.target.closest(".open-grade-btn");
    if (!btn) return;

    openGradeModal(
        btn.dataset.id,
        btn.dataset.student,
        btn.dataset.text,
        btn.dataset.grade ? Number(btn.dataset.grade) : null,
        btn.dataset.file
    );
});


// Initialize on load
initDashboard();
