<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Courses - LMS Dashboard</title>

    <!-- Tabler CSS -->
    <link href="<?= base_url('assets/tabler/css/tabler.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/tabler/css/tabler-flags.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/tabler/css/tabler-payments.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/tabler/css/tabler-vendors.min.css') ?>" rel="stylesheet">

    <style>
        .course-card {
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            overflow: hidden;
            border-radius: .375rem;
        }

        .course-card img {
            height: 180px;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }

        .course-card:hover img {
            transform: scale(1.05);
        }

        .course-body {
            padding: 1rem;
            transition: background-color 0.3s;
        }

        .course-rating {
            color: #f59e0b; /* golden stars */
        }

        /* Optional: hover overlay with description */
        .course-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.5);
            color: #fff;
            opacity: 0;
            transition: opacity 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 1rem;
        }

        .course-card:hover .course-overlay {
            opacity: 1;
        }

        .course-card-container {
            position: relative;
        }
    </style>
</head>
<body>
<div class="page">
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">Available Courses</h2>
                    <p class="text-muted">Browse and enroll in courses to enhance your skills.</p>
                </div>
            </div>
        </div>

        <div class="row row-deck row-cards">
            <?php
            $courses = [
                [
                    'title' => 'Web Development Bootcamp',
                    'instructor' => 'John Doe',
                    'rating' => 4.7,
                    'image' => base_url('assets/images/course1.jpg'),
                    'link' => '#',
                    'description' => 'Learn full stack web development from scratch with hands-on projects.'
                ],
                [
                    'title' => 'Python for Data Science',
                    'instructor' => 'Jane Smith',
                    'rating' => 4.8,
                    'image' => base_url('assets/images/course2.jpg'),
                    'link' => '#',
                    'description' => 'Master Python and data analysis techniques for real-world applications.'
                ],
                [
                    'title' => 'Digital Marketing 101',
                    'instructor' => 'Emily Clark',
                    'rating' => 4.5,
                    'image' => base_url('assets/images/course3.jpg'),
                    'link' => '#',
                    'description' => 'Learn the fundamentals of digital marketing and grow your business online.'
                ],
                [
                    'title' => 'Graphic Design Masterclass',
                    'instructor' => 'Michael Lee',
                    'rating' => 4.6,
                    'image' => base_url('assets/images/course4.jpg'),
                    'link' => '#',
                    'description' => 'Become a pro in graphic design using tools like Photoshop and Illustrator.'
                ],
            ];

            foreach($courses as $course): ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="course-card-container">
                        <div class="card course-card">
                            <img src="<?= $course['image'] ?>" alt="<?= $course['title'] ?>">
                            <div class="course-overlay">
                                <p><?= $course['description'] ?></p>
                            </div>
                            <div class="card-body course-body">
                                <h4 class="card-title"><?= $course['title'] ?></h4>
                                <p class="card-text text-muted">Instructor: <?= $course['instructor'] ?></p>
                                <p class="card-text course-rating">
                                    <?= str_repeat('★', floor($course['rating'])) ?>
                                    <?= str_repeat('☆', 5 - floor($course['rating'])) ?>
                                    (<?= $course['rating'] ?>)
                                </p>
                                <a href="<?= $course['link'] ?>" class="btn btn-primary w-100">View Course</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <footer class="footer footer-transparent d-print-none mt-4">
            <div class="container">
                <div class="row text-center align-items-center">
                    <div class="col-12">
                        <p class="text-muted mb-0">Page rendered in <strong>{elapsed_time}</strong> seconds. 
                        <?php echo  (ENVIRONMENT === 'development') ?  'CodeIgniter Version <strong>' . CI_VERSION . '</strong>' : '' ?></p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>

<script src="<?= base_url('assets/tabler/js/tabler.min.js') ?>"></script>
</body>
</html>
