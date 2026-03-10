<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>KABAGA Instructor Panel</title>

<!-- Tabler CSS -->
<link href="<?= base_url('assets/tabler/css/tabler.min.css') ?>" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

<!-- Toastify for notifications -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

<style>
body {
    font-family: 'Inter', sans-serif;
    background: #f6f8fb;
    transition: background 0.3s;
}

.sidebar {
    width: 260px;
    position: fixed;
    height: 100vh;
    background: #fff;
    padding: 20px;
    border-right: 1px solid #eee;
}
.sidebar h3 {
    color: #0d6efd;
    margin-bottom: 2rem;
    font-weight: 700;
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

/* Course Builder Cards */
.builder-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    margin-bottom: 20px;
    cursor: grab;
    transition: all 0.3s ease;
}
.builder-card:hover { transform: translateY(-5px); box-shadow:0 12px 24px rgba(0,0,0,0.15); }
.builder-card.dragging { opacity: 0.6; }

/* Video upload preview */
.video-preview {
    width: 100%;
    max-height: 300px;
    object-fit: cover;
    border-radius: 8px;
    margin-top: 10px;
}

/* Quiz section */
.quiz-card {
    background: #f1f5f9;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 10px;
}

/* Buttons */
.btn-lift {
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.btn-lift:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(0,0,0,0.18); }
</style>

<!-- Sortable.js -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<!-- Toastify -->
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar animate__animated animate__fadeInLeft animate__fast">
    <h3>KABAGA Instructor</h3>
    <a href="#">Dashboard</a>
    <a href="#">My Courses</a>
    <a href="#">Create Course</a>
    <a href="#">Quizzes</a>
    <a href="#">Student Progress</a>
</div>

<!-- Main -->
<div class="main">
<h2 class="mb-4 animate__animated animate__fadeInDown animate__fast">Course Builder</h2>

<!-- New Course Form -->
<div class="card p-4 mb-4 animate__animated animate__fadeInUp animate__fast">
    <h4>Create a New Course</h4>
    <form id="newCourseForm">
        <div class="mb-3">
            <label class="form-label">Course Title</label>
            <input type="text" class="form-control" id="courseTitle" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Course Description</label>
            <textarea class="form-control" id="courseDescription" rows="3" required></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Upload Course Image</label>
            <input type="file" class="form-control" id="courseImage" accept="image/*">
            <img id="courseImagePreview" class="video-preview" style="display:none;">
        </div>
        <button type="submit" class="btn btn-primary btn-lift">Create Course</button>
    </form>
</div>

<!-- Lessons Builder -->
<div class="card p-4 mb-4 animate__animated animate__fadeInUp animate__fast animate__delay-1s">
    <h4>Lessons & Videos</h4>
    <div id="lessonsContainer">
        <div class="builder-card" draggable="true">
            <label>Lesson Title</label>
            <input type="text" class="form-control mb-2" placeholder="Lesson name">
            <label>Upload Video</label>
            <input type="file" class="form-control video-upload" accept="video/*">
            <video class="video-preview" controls style="display:none;"></video>
        </div>
    </div>
    <button id="addLessonBtn" class="btn btn-secondary btn-lift mt-3">Add Lesson</button>
</div>

<!-- Quizzes Builder -->
<div class="card p-4 animate__animated animate__fadeInUp animate__fast animate__delay-2s">
    <h4>Quizzes</h4>
    <div id="quizzesContainer">
        <div class="quiz-card">
            <label>Question</label>
            <input type="text" class="form-control mb-2" placeholder="Enter question">
            <label>Answer</label>
            <input type="text" class="form-control mb-2" placeholder="Correct answer">
        </div>
    </div>
    <button id="addQuizBtn" class="btn btn-secondary btn-lift mt-2">Add Quiz</button>
</div>
</div>

<!-- Scripts -->
<script>
// Course Image Preview
document.getElementById('courseImage').addEventListener('change', function(e){
    const preview = document.getElementById('courseImagePreview');
    preview.src = URL.createObjectURL(e.target.files[0]);
    preview.style.display = 'block';
});

// Add Lesson
document.getElementById('addLessonBtn').addEventListener('click', function(){
    const container = document.getElementById('lessonsContainer');
    const lessonCard = document.createElement('div');
    lessonCard.className = 'builder-card';
    lessonCard.draggable = true;
    lessonCard.innerHTML = `
        <label>Lesson Title</label>
        <input type="text" class="form-control mb-2" placeholder="Lesson name">
        <label>Upload Video</label>
        <input type="file" class="form-control video-upload" accept="video/*">
        <video class="video-preview" controls style="display:none;"></video>
    `;
    container.appendChild(lessonCard);
});

// Video Preview
document.addEventListener('change', function(e){
    if(e.target.classList.contains('video-upload')){
        const video = e.target.nextElementSibling;
        video.src = URL.createObjectURL(e.target.files[0]);
        video.style.display = 'block';
    }
});

// Add Quiz
document.getElementById('addQuizBtn').addEventListener('click', function(){
    const container = document.getElementById('quizzesContainer');
    const quizCard = document.createElement('div');
    quizCard.className = 'quiz-card';
    quizCard.innerHTML = `
        <label>Question</label>
        <input type="text" class="form-control mb-2" placeholder="Enter question">
        <label>Answer</label>
        <input type="text" class="form-control mb-2" placeholder="Correct answer">
    `;
    container.appendChild(quizCard);
    Toastify({ text:"Quiz added!", duration:2000, gravity:"top", position:"right", backgroundColor:"#0d6efd" }).showToast();
});

// Drag & Drop Lessons
Sortable.create(document.getElementById('lessonsContainer'), { animation:150 });

// Submit New Course Form
document.getElementById('newCourseForm').addEventListener('submit', function(e){
    e.preventDefault();
    Toastify({ text:"Course Created Successfully!", duration:2000, gravity:"top", position:"right", backgroundColor:"green" }).showToast();
});
</script>

<script src="<?= base_url('assets/tabler/js/tabler.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

</body>
</html>