<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>KABAGA Admin Panel</title>

<!-- Tabler CSS -->
<link href="<?= base_url('assets/tabler/css/tabler.min.css') ?>" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

<!-- Toastify for notifications -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

<style>
body {
    font-family: 'Inter', sans-serif;
    background-color: #f6f8fb;
    transition: background 0.3s;
}

.sidebar {
    width: 260px;
    position: fixed;
    height: 100vh;
    background: #fff;
    padding: 20px;
    border-right: 1px solid #eee;
    transition: all 0.3s ease;
}
.sidebar h3 {
    margin-bottom: 2rem;
    font-weight: 700;
    color: #0d6efd;
}
.sidebar a {
    display: flex;
    align-items: center;
    padding: 10px 12px;
    margin-bottom: 8px;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    font-weight: 500;
    transition: all 0.2s ease;
}
.sidebar a:hover {
    background: #e7f0ff;
    color: #0d6efd;
}

.main {
    margin-left: 280px;
    padding: 30px;
}

/* Dashboard cards */
.dashboard-card {
    border-radius: 12px;
    padding: 20px;
    background: #fff;
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    cursor: pointer;
}
.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
}
.dashboard-card h3 {
    font-size: 2rem;
    font-weight: 700;
}
.dashboard-card p {
    color: #6c757d;
    margin: 0;
}

/* Tables */
.table-container {
    margin-top: 30px;
}
.table th, .table td { vertical-align: middle; }

/* Analytics charts */
.chart-container {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    margin-top: 20px;
}

/* Dark Mode */
body.dark-mode { background: #121212; color: #ddd; }
body.dark-mode .sidebar { background: #1e1e1e; border-color: #333; }
body.dark-mode .dashboard-card { background: #1f1f1f; box-shadow: 0 8px 20px rgba(0,0,0,0.5); }
body.dark-mode .chart-container { background: #1f1f1f; box-shadow: 0 8px 20px rgba(0,0,0,0.5); }
body.dark-mode .table { color: #ddd; }

/* Badges */
.badge-top { background: linear-gradient(90deg,#ffd700,#f59e0b); color:#000; font-weight:600; }
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Toastify -->
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<!-- Sortable.js for drag/drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

</head>
<body>

<!-- Sidebar -->
<div class="sidebar animate__animated animate__fadeInLeft animate__fast">
    <h3>KABAGA Admin</h3>
    <a href="#"><i class="ti ti-home"></i> Dashboard</a>
    <a href="#" id="manageCoursesBtn"><i class="ti ti-book"></i> Courses</a>
    <a href="#" id="manageInstructorsBtn"><i class="ti ti-user"></i> Instructors</a>
    <a href="#" id="manageStudentsBtn"><i class="ti ti-users"></i> Students</a>
    <a href="#"><i class="ti ti-certificate"></i> Certificates</a>
    <a href="#"><i class="ti ti-chart-pie"></i> Analytics</a>
    <a href="#" id="toggleDarkMode"><i class="ti ti-moon"></i> Dark Mode</a>
</div>

<!-- Main Content -->
<div class="main">
<h2 class="mb-4 animate__animated animate__fadeInDown animate__fast">Admin Dashboard</h2>

<div class="row g-4">
    <div class="col-md-3 animate__animated animate__fadeInUp animate__fast">
        <div class="dashboard-card">
            <h3>120</h3>
            <p>Total Students</p>
        </div>
    </div>
    <div class="col-md-3 animate__animated animate__fadeInUp animate__fast animate__delay-1s">
        <div class="dashboard-card">
            <h3>25</h3>
            <p>Total Courses</p>
        </div>
    </div>
    <div class="col-md-3 animate__animated animate__fadeInUp animate__fast animate__delay-2s">
        <div class="dashboard-card">
            <h3>8</h3>
            <p>Total Instructors</p>
        </div>
    </div>
    <div class="col-md-3 animate__animated animate__fadeInUp animate__fast animate__delay-3s">
        <div class="dashboard-card">
            <h3>320</h3>
            <p>Certificates Issued</p>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row chart-container">
    <div class="col-md-6">
        <canvas id="studentsChart"></canvas>
    </div>
    <div class="col-md-6">
        <canvas id="coursesChart"></canvas>
    </div>
</div>

<!-- Latest Activity Table -->
<div class="table-container">
    <h4 class="mt-4 mb-3">Latest Enrollments</h4>
    <table class="table table-hover table-striped">
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Course</th>
                <th>Status</th>
                <th>Enrolled At</th>
                <th>Badge</th>
            </tr>
        </thead>
        <tbody id="latestEnrollments">
            <tr draggable="true">
                <td>John Doe</td>
                <td>Python for Data Science</td>
                <td><span class="badge bg-success">Active</span></td>
                <td>2026-03-05</td>
                <td><span class="badge badge-top">Top Performer</span></td>
            </tr>
            <tr draggable="true">
                <td>Jane Smith</td>
                <td>Web Development Bootcamp</td>
                <td><span class="badge bg-warning">Pending</span></td>
                <td>2026-03-04</td>
                <td></td>
            </tr>
            <tr draggable="true">
                <td>Michael Lee</td>
                <td>Graphic Design Masterclass</td>
                <td><span class="badge bg-success">Active</span></td>
                <td>2026-03-03</td>
                <td><span class="badge badge-top">Top Performer</span></td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Management Modals -->
<!-- Courses -->
<div class="modal fade" id="coursesModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content p-4">
        <h5>Manage Courses</h5>
        <ul id="courseList">
            <li class="mb-2 p-2 bg-light rounded shadow-sm">Python Bootcamp <button class="btn btn-sm btn-danger float-end">Delete</button></li>
            <li class="mb-2 p-2 bg-light rounded shadow-sm">Web Development <button class="btn btn-sm btn-danger float-end">Delete</button></li>
        </ul>
    </div>
  </div>
</div>

<!-- Instructors -->
<div class="modal fade" id="instructorsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content p-4">
        <h5>Manage Instructors</h5>
        <ul>
            <li class="mb-2 p-2 bg-light rounded shadow-sm">John Doe <button class="btn btn-sm btn-danger float-end">Remove</button></li>
            <li class="mb-2 p-2 bg-light rounded shadow-sm">Jane Smith <button class="btn btn-sm btn-danger float-end">Remove</button></li>
        </ul>
    </div>
  </div>
</div>

<!-- Students -->
<div class="modal fade" id="studentsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content p-4">
        <h5>Manage Students</h5>
        <ul>
            <li class="mb-2 p-2 bg-light rounded shadow-sm">John Doe <button class="btn btn-sm btn-danger float-end">Remove</button></li>
            <li class="mb-2 p-2 bg-light rounded shadow-sm">Jane Smith <button class="btn btn-sm btn-danger float-end">Remove</button></li>
        </ul>
    </div>
  </div>
</div>

</div>

<!-- Scripts -->
<script>
// Chart.js
const ctxStudents = document.getElementById('studentsChart').getContext('2d');
const studentsChart = new Chart(ctxStudents, { type: 'bar', data: { labels: ['Jan','Feb','Mar','Apr','May','Jun'], datasets:[{label:'New Students',data:[12,19,10,15,22,18],backgroundColor:'#0d6efd'}]}, options:{responsive:true,plugins:{legend:{display:false}}}});
const ctxCourses = document.getElementById('coursesChart').getContext('2d');
const coursesChart = new Chart(ctxCourses, { type:'line', data:{ labels:['Jan','Feb','Mar','Apr','May','Jun'], datasets:[{label:'Courses Created',data:[2,3,5,4,6,7],borderColor:'#0d6efd',backgroundColor:'rgba(13,110,253,0.2)',tension:0.4}]}, options:{responsive:true,plugins:{legend:{display:false}}}});

// Toast Notifications Example
function showToast(msg, bg='#0d6efd') {
    Toastify({ text: msg, duration:3000, gravity:'top', position:'right', backgroundColor:bg }).showToast();
}

// Dark Mode Toggle
document.getElementById('toggleDarkMode').addEventListener('click',function(){
    document.body.classList.toggle('dark-mode');
    showToast('Dark mode toggled','purple');
});

// Management Modals
document.getElementById('manageCoursesBtn').addEventListener('click',()=> new bootstrap.Modal(document.getElementById('coursesModal')).show());
document.getElementById('manageInstructorsBtn').addEventListener('click',()=> new bootstrap.Modal(document.getElementById('instructorsModal')).show());
document.getElementById('manageStudentsBtn').addEventListener('click',()=> new bootstrap.Modal(document.getElementById('studentsModal')).show());

// Drag & Drop for Latest Enrollments
Sortable.create(document.getElementById('latestEnrollments'), { animation:150, handle:'td' });
</script>

<script src="<?= base_url('assets/tabler/js/tabler.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

</body>
</html>