<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="utf-8">
<title>KABAGA Academy LMS</title>

<link href="<?= base_url('assets/tabler/css/tabler.min.css') ?>" rel="stylesheet">

<style>

/* GLOBAL */

body{
background:#f6f8fb;
font-family:Inter, sans-serif;
}

/* SIDEBAR */

.sidebar{
width:240px;
background:white;
height:100vh;
position:fixed;
padding:20px;
border-right:1px solid #eee;
}

.sidebar a{
display:block;
padding:10px;
border-radius:8px;
margin-bottom:6px;
text-decoration:none;
color:#333;
}

.sidebar a:hover{
background:#f0f4ff;
}

/* MAIN */

.main{
margin-left:260px;
padding:30px;
}

/* COURSE PLAYER */

.video-player{
background:black;
height:420px;
border-radius:10px;
margin-bottom:20px;
}

/* LESSON LIST */

.lesson-list{
background:white;
border-radius:10px;
padding:15px;
max-height:420px;
overflow:auto;
}

/* QUIZ CARD */

.quiz-card{
background:white;
border-radius:10px;
padding:20px;
box-shadow:0 6px 20px rgba(0,0,0,.05);
}

/* ANALYTICS */

.analytics-card{
background:white;
border-radius:12px;
padding:20px;
text-align:center;
box-shadow:0 6px 20px rgba(0,0,0,.05);
}

/* LEADERBOARD */

.leaderboard{
background:white;
padding:20px;
border-radius:10px;
}

/* NOTIFICATION */

.notification{
position:relative;
}

.notification-badge{
position:absolute;
top:-5px;
right:-5px;
background:red;
color:white;
border-radius:50%;
font-size:11px;
padding:2px 6px;
}

</style>

</head>

<body>

<!-- SIDEBAR -->

<div class="sidebar">

<h3>KABAGA</h3>

<a href="#">Dashboard</a>
<a href="#">My Courses</a>
<a href="#">Certificates</a>
<a href="#">Leaderboard</a>
<a href="#">Profile</a>

</div>



<!-- MAIN -->

<div class="main">

<!-- HEADER -->

<div class="d-flex justify-content-between mb-4">

<h2>Student Dashboard</h2>

<div>

<span class="notification me-3">

🔔
<span class="notification-badge">3</span>

</span>

<img src="https://i.pravatar.cc/40" class="rounded-circle">

</div>

</div>



<!-- ANALYTICS -->

<div class="row mb-4">

<div class="col-lg-3">
<div class="analytics-card">
<h3>12</h3>
Courses Enrolled
</div>
</div>

<div class="col-lg-3">
<div class="analytics-card">
<h3>7</h3>
Courses Completed
</div>
</div>

<div class="col-lg-3">
<div class="analytics-card">
<h3>24h</h3>
Learning Hours
</div>
</div>

<div class="col-lg-3">
<div class="analytics-card">
<h3>5</h3>
Badges Earned 🏆
</div>
</div>

</div>



<!-- COURSE PLAYER -->

<div class="row mb-4">

<div class="col-lg-8">

<div class="video-player d-flex align-items-center justify-content-center text-white">

Video Lesson Player

</div>

</div>

<div class="col-lg-4">

<div class="lesson-list">

<h4>Lessons</h4>

<ul class="list-group">

<li class="list-group-item">Lesson 1 - Introduction</li>
<li class="list-group-item">Lesson 2 - Fundamentals</li>
<li class="list-group-item">Lesson 3 - Practical Demo</li>
<li class="list-group-item">Lesson 4 - Project</li>
<li class="list-group-item">Lesson 5 - Final Exam</li>

</ul>

</div>

</div>

</div>



<!-- QUIZ UI -->

<div class="quiz-card mb-4">

<h4>Quiz</h4>

<p>What does HTML stand for?</p>

<div class="form-check">
<input class="form-check-input" type="radio" name="quiz">
<label class="form-check-label">Hyper Text Markup Language</label>
</div>

<div class="form-check">
<input class="form-check-input" type="radio" name="quiz">
<label class="form-check-label">High Transfer Machine Language</label>
</div>

<div class="form-check">
<input class="form-check-input" type="radio" name="quiz">
<label class="form-check-label">Hyper Tool Markup Language</label>
</div>

<button class="btn btn-primary mt-3">Submit Answer</button>

</div>



<!-- CERTIFICATE -->

<div class="quiz-card mb-4">

<h4>Certificate</h4>

<p>You completed the course!</p>

<button class="btn btn-success">

Download Certificate

</button>

</div>



<!-- LEADERBOARD -->

<div class="leaderboard">

<h4>Top Learners</h4>

<table class="table">

<tr>
<th>Rank</th>
<th>Name</th>
<th>Points</th>
</tr>

<tr>
<td>1</td>
<td>Alice</td>
<td>980</td>
</tr>

<tr>
<td>2</td>
<td>John</td>
<td>870</td>
</tr>

<tr>
<td>3</td>
<td>Maria</td>
<td>850</td>
</tr>

</table>

</div>

</div>

<script src="<?= base_url('assets/tabler/js/tabler.min.js') ?>"></script>

</body>
</html>