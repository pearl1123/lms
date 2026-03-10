<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="utf-8">
<title>KABAGA LMS Dashboard</title>

<link href="<?= base_url('assets/tabler/css/tabler.min.css') ?>" rel="stylesheet">

<style>

/* DASHBOARD */

body{
background:#f6f8fb;
}

/* FEATURED CAROUSEL */

.carousel-card{
border-radius:16px;
overflow:hidden;
box-shadow:0 10px 30px rgba(0,0,0,.08);
}

.carousel-card img{
height:220px;
object-fit:cover;
}

/* COURSE CARD */

.course-card{
border:none;
border-radius:14px;
overflow:hidden;
background:white;
transition:.25s;
cursor:pointer;
}

.course-card:hover{
transform:translateY(-6px);
box-shadow:0 14px 40px rgba(0,0,0,.15);
}

.course-card img{
height:180px;
object-fit:cover;
}

.course-body{
padding:1rem;
}

/* FAVORITE BUTTON */

.favorite{
position:absolute;
top:10px;
right:10px;
background:white;
border-radius:50%;
width:34px;
height:34px;
display:flex;
align-items:center;
justify-content:center;
box-shadow:0 6px 14px rgba(0,0,0,.2);
cursor:pointer;
}

.favorite.active{
color:red;
}

/* BADGES */

.badge-complete{
background:#22c55e;
color:white;
border-radius:20px;
padding:4px 10px;
font-size:12px;
position:absolute;
top:10px;
left:10px;
}

/* ANALYTICS CARD */

.analytics-card{
border-radius:16px;
background:white;
padding:20px;
box-shadow:0 8px 20px rgba(0,0,0,.08);
}

/* DARK MODE */

.dark-mode{
background:#0f172a;
color:white;
}

.dark-mode .course-card{
background:#1e293b;
}

.dark-mode .analytics-card{
background:#1e293b;
}

</style>

</head>

<body>

<div class="page">
<div class="container-xl">

<!-- HEADER -->

<div class="d-flex justify-content-between align-items-center mb-4">

<div>
<h2 class="page-title">Welcome back 👋</h2>
<p class="text-muted">Continue your learning journey.</p>
</div>

<button class="btn btn-outline-primary" onclick="toggleDarkMode()">Toggle Dark Mode</button>

</div>



<!-- STUDENT ANALYTICS -->

<div class="row mb-4">

<div class="col-lg-3">

<div class="analytics-card text-center">
<h3>12</h3>
<p>Courses Enrolled</p>
</div>

</div>

<div class="col-lg-3">

<div class="analytics-card text-center">
<h3>7</h3>
<p>Courses Completed</p>
</div>

</div>

<div class="col-lg-3">

<div class="analytics-card text-center">
<h3>18h</h3>
<p>Learning Hours</p>
</div>

</div>

<div class="col-lg-3">

<div class="analytics-card text-center">
<h3>5</h3>
<p>Badges Earned 🏆</p>
</div>

</div>

</div>



<!-- FEATURED COURSES CAROUSEL -->

<h3 class="mb-3">Featured Courses</h3>

<div id="featuredCarousel" class="carousel slide mb-5" data-bs-ride="carousel">

<div class="carousel-inner">

<div class="carousel-item active">

<div class="carousel-card">

<img src="<?= base_url('assets/images/course1.jpg') ?>">

<div class="p-3">

<h4>Full Stack Web Development</h4>

<button class="btn btn-primary" onclick="openPreview('Full Stack Web Development','Learn modern web development with real projects.')">
Preview Course
</button>

</div>

</div>

</div>

<div class="carousel-item">

<div class="carousel-card">

<img src="<?= base_url('assets/images/course2.jpg') ?>">

<div class="p-3">

<h4>Python for Data Science</h4>

<button class="btn btn-primary" onclick="openPreview('Python for Data Science','Master data science using Python.')">
Preview Course
</button>

</div>

</div>

</div>

</div>

</div>



<!-- COURSE GRID -->

<h3 class="mb-3">All Courses</h3>

<div class="row row-cards">

<?php

$courses = [

[
'title'=>'Web Development Bootcamp',
'instructor'=>'John Doe',
'image'=>base_url('assets/images/course1.jpg'),
'progress'=>70
],

[
'title'=>'Python for Data Science',
'instructor'=>'Jane Smith',
'image'=>base_url('assets/images/course2.jpg'),
'progress'=>40
],

[
'title'=>'Digital Marketing',
'instructor'=>'Emily Clark',
'image'=>base_url('assets/images/course3.jpg'),
'progress'=>90
],

[
'title'=>'Graphic Design',
'instructor'=>'Michael Lee',
'image'=>base_url('assets/images/course4.jpg'),
'progress'=>20
]

];

foreach($courses as $course):

?>

<div class="col-sm-6 col-lg-3">

<div class="card course-card">

<div class="position-relative">

<img src="<?= $course['image'] ?>">

<div class="favorite" onclick="toggleFavorite(this)">❤</div>

<?php if($course['progress']>=90): ?>

<span class="badge-complete">Completed</span>

<?php endif; ?>

</div>

<div class="course-body">

<h4><?= $course['title'] ?></h4>

<p class="text-muted">
Instructor:
<a href="#" onclick="openInstructor('<?= $course['instructor'] ?>')">
<?= $course['instructor'] ?>
</a>
</p>

<div class="progress progress-sm mb-2">

<div class="progress-bar bg-primary"
style="width:<?= $course['progress'] ?>%">
</div>

</div>

<button class="btn btn-primary w-100"
onclick="openPreview('<?= $course['title'] ?>','Course preview coming soon')">

Open Course

</button>

</div>

</div>

</div>

<?php endforeach; ?>

</div>

</div>
</div>



<!-- COURSE PREVIEW MODAL -->

<div class="modal fade" id="coursePreview">

<div class="modal-dialog modal-dialog-centered">

<div class="modal-content">

<div class="modal-header">
<h5 id="previewTitle"></h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<p id="previewDesc"></p>
</div>

</div>

</div>

</div>



<!-- INSTRUCTOR MODAL -->

<div class="modal fade" id="instructorModal">

<div class="modal-dialog modal-dialog-centered">

<div class="modal-content">

<div class="modal-header">
<h5 id="instructorName"></h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<p>Experienced instructor in this field with many years of professional teaching.</p>
</div>

</div>

</div>

</div>



<script src="<?= base_url('assets/tabler/js/tabler.min.js') ?>"></script>

<script>

/* FAVORITE */

function toggleFavorite(el){
el.classList.toggle("active");
}

/* DARK MODE */

function toggleDarkMode(){
document.body.classList.toggle("dark-mode");
}

/* COURSE PREVIEW */

function openPreview(title,desc){

document.getElementById("previewTitle").innerText=title;
document.getElementById("previewDesc").innerText=desc;

new bootstrap.Modal(document.getElementById('coursePreview')).show();
}

/* INSTRUCTOR POPUP */

function openInstructor(name){

document.getElementById("instructorName").innerText=name;

new bootstrap.Modal(document.getElementById('instructorModal')).show();
}

</script>

</body>
</html>