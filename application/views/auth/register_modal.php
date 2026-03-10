<!-- SIGN UP MODAL -->
<div class="modal fade" id="signUpModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 rounded-4 overflow-hidden shadow-lg animate__animated animate__fadeInDown animate__faster" style="backdrop-filter: blur(5px);">

      <!-- Modal Header / Logo -->
      <div class="text-center p-4">
        <a href="<?= base_url(); ?>" class="navbar-brand navbar-brand-autodark mb-3 d-inline-block">
          <img src="<?= base_url('assets/tabler/img/logo.png'); ?>" height="120" alt="KABAGA Logo" class="shadow-sm rounded-circle">
        </a>
        <h4 class="fw-bold text-primary mb-2 animate__animated animate__fadeInUp animate__faster">Create Your Account</h4>
        <p class="text-muted animate__animated animate__fadeInUp animate__faster animate__delay-0-1s">Register quickly and start your learning journey today!</p>
      </div>

      <!-- Registration Form -->
      <form id="registerForm" action="<?= base_url('auth/register_process'); ?>" method="post" autocomplete="off" novalidate class="p-3">
        <div class="row g-3">

          <!-- Employee ID -->
          <div class="col-12 position-relative animate__animated animate__bounceIn animate__faster animate__delay-0-1s">
            <label class="form-label fw-semibold">Employee ID</label>
            <input type="text" name="employee_id" id="employee_id" class="form-control form-control-lg rounded-pill shadow-sm input-lift pr-5" placeholder="Enter Employee ID" required>
            <span id="employee_icon" class="position-absolute end-3 top-50 translate-middle-y" style="font-size:1.2rem; display:none;"></span>
            <div id="employee_tooltip">Enter your employee ID exactly as provided by HR.</div>
            <small id="employee_status" class="text-muted d-block mt-1"></small>
          </div>

          <!-- Name -->
          <div class="col-12 animate__animated animate__bounceIn animate__faster animate__delay-0-2s">
            <label class="form-label fw-semibold">Name</label>
            <input type="text" name="name" id="name" class="form-control form-control-lg rounded-pill shadow-sm input-lift" placeholder="Employee Name" readonly required>
          </div>

          <!-- Password -->
          <div class="col-12 animate__animated animate__bounceIn animate__faster animate__delay-0-3s">
            <label class="form-label fw-semibold">Password</label>
            <div class="input-group input-group-lg input-lift">
              <input type="password" name="password" id="password" class="form-control rounded-pill shadow-sm" placeholder="Password" autocomplete="off" required>
              <span class="input-group-text bg-white border-0">
                <a href="#" class="link-secondary" title="Show password" data-bs-toggle="tooltip">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
                    <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>
                  </svg>
                </a>
              </span>
            </div>
          </div>

          <!-- Confirm Password -->
          <div class="col-12 animate__animated animate__bounceIn animate__faster animate__delay-0-4s">
            <label class="form-label fw-semibold">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control form-control-lg rounded-pill shadow-sm input-lift" placeholder="Confirm Password" required>
            <small id="password_match" class="text-danger"></small>
          </div>

          <!-- Terms -->
          <div class="col-12 animate__animated animate__bounceIn animate__faster animate__delay-0-5s">
            <label class="form-check">
              <input type="checkbox" name="agree_terms" class="form-check-input" required/>
              <span class="form-check-label">
                Agree to the <a href="#" id="openTermsPanel">terms and policy</a>.
              </span>
            </label>
          </div>

          <!-- Submit Button -->
          <div class="col-12 text-center mt-3 animate__animated animate__bounceIn animate__faster animate__delay-0-6s">
            <button type="submit" id="register_submit" class="btn btn-primary btn-lg w-75 rounded-pill shadow-sm btn-lift" style="background: linear-gradient(90deg,#6dabcf,#5a9ec1); transition: all 0.3s ease;">Create Account</button>
          </div>

        </div>
      </form>

      <div class="text-center text-secondary mt-4 fw-semibold animate__animated animate__fadeInUp animate__faster animate__delay-0-7s">
        Already have an account? <a href="<?= base_url('index.php/auth/login'); ?>">Sign in</a>
      </div>

      <!-- Enterprise Terms Slide Panel -->
<!-- Enterprise Terms Slide Panel -->
<div id="termsPanel" class="terms-panel bg-white shadow-lg rounded-start">
  <div class="terms-header d-flex justify-content-between align-items-center p-3 border-bottom">
    <h5 class="m-0">KABAGA Academy Terms & Policies</h5>
    <button type="button" id="closeTermsPanel" class="btn-close"></button>
  </div>
  <div class="terms-body d-flex">
    <!-- TOC -->
    <div class="terms-toc border-end p-3">
      <ul class="list-unstyled">
        <li><a href="#acceptance">1. Acceptance</a></li>
        <li><a href="#eligibility">2. Eligibility</a></li>
        <li><a href="#account">3. Account Security</a></li>
        <li><a href="#use">4. Acceptable Use</a></li>
        <li><a href="#ip">5. Intellectual Property</a></li>
        <li><a href="#privacy">6. Data Privacy</a></li>
        <li><a href="#monitoring">7. System Monitoring</a></li>
        <li><a href="#completion">8. Completion & Certifications</a></li>
        <li><a href="#conduct">9. Code of Conduct</a></li>
        <li><a href="#suspension">10. Account Suspension</a></li>
        <li><a href="#updates">11. Policy Updates</a></li>
        <li><a href="#contact">12. Contact Info</a></li>
      </ul>
    </div>
    <!-- Content -->
    <div class="terms-content flex-grow-1 p-3" id="termsContent">
      <section id="acceptance">
        <h6 class="fw-bold">1. Acceptance of Terms</h6>
        <p>
          By accessing or using KABAGA Academy, the Learning Management System (LMS) for the Lung Center of the Philippines,
          you acknowledge that you have read, understood, and agree to comply with these Terms and Policies. 
          If you do not agree with any part, you must immediately discontinue use.
        </p>
      </section>
      <section id="eligibility">
        <h6 class="fw-bold">2. User Eligibility</h6>
        <p>
          Access is limited to authorized employees, trainees, and personnel of the Lung Center of the Philippines.
          Users must provide accurate information during registration and must not share credentials.
        </p>
      </section>
      <section id="account">
        <h6 class="fw-bold">3. Account Security</h6>
        <p>
          Each user is responsible for maintaining confidentiality of login credentials.
          Unauthorized use or disclosure of account information is strictly prohibited.
          Any activity performed under your account is your responsibility.
        </p>
      </section>
      <section id="use">
        <h6 class="fw-bold">4. Acceptable Use Policy</h6>
        <ul>
          <li>Use the platform solely for authorized learning and training activities.</li>
          <li>Do not upload malware, viruses, or malicious files.</li>
          <li>Do not attempt to access restricted system areas without permission.</li>
          <li>Do not share confidential content outside the organization.</li>
          <li>Compliance with the Lung Center’s IT policies is mandatory.</li>
        </ul>
      </section>
      <section id="ip">
        <h6 class="fw-bold">5. Intellectual Property</h6>
        <p>
          All content, including course materials, videos, documents, and images, 
          are protected by intellectual property laws. Use is limited to internal training.
        </p>
      </section>
      <section id="privacy">
        <h6 class="fw-bold">6. Data Privacy</h6>
        <p>
          Personal data collected will be processed according to the Philippine Data Privacy Act (RA 10173)
          and the Lung Center’s internal privacy policies. Users consent to collection, processing, and storage of data for training and administrative purposes.
        </p>
      </section>
      <section id="monitoring">
        <h6 class="fw-bold">7. System Monitoring</h6>
        <p>
          User activities may be logged and monitored for security, compliance, and system improvement purposes. 
          Unauthorized activities will be investigated.
        </p>
      </section>
      <section id="completion">
        <h6 class="fw-bold">8. Course Completion & Certifications</h6>
        <p>
          Certificates are issued based on completion of lessons, quizzes, and instructor validations.
          Any attempts to falsify completion may result in account suspension or revocation of certification.
        </p>
      </section>
      <section id="conduct">
        <h6 class="fw-bold">9. Code of Conduct</h6>
        <ul>
          <li>Maintain professional behavior on the platform.</li>
          <li>No harassment, offensive language, or inappropriate content.</li>
          <li>Respect confidentiality of patient-related materials.</li>
          <li>Report any suspicious activity to the system administrator immediately.</li>
        </ul>
      </section>
      <section id="suspension">
        <h6 class="fw-bold">10. Account Suspension</h6>
        <p>
          Accounts may be suspended or terminated for violating these Terms, 
          security breaches, or unauthorized activities. Reinstatement is at the discretion of the system administrator.
        </p>
      </section>
      <section id="updates">
        <h6 class="fw-bold">11. Policy Updates</h6>
        <p>
          Terms and Policies may be updated periodically to reflect legal, regulatory, or organizational changes.
          Continued use constitutes acceptance of the updated terms.
        </p>
      </section>
      <section id="contact">
        <h6 class="fw-bold">12. Contact Information</h6>
        <p>
          For questions, concerns, or reporting violations, contact the Management Information Systems Division or the LMS Administrator at the Lung Center of the Philippines.
        </p>
      </section>
    </div>
  </div>
  <div class="terms-footer border-top p-3 d-flex justify-content-end">
    <button type="button" id="acceptTermsBtn" class="btn btn-primary">I Agree to the Terms</button>
  </div>
</div>

    </div>
  </div>
</div>

<!-- Styles for Slide Panel -->
<style>
.terms-panel {
  position: absolute;
  top: 0;
  left: 100%;
  width: 90%;
  max-width: 900px;
  height: 100%;
  background: #fff;
  z-index: 1050;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition: left 0.4s ease;
}
.terms-panel.show {
  left: 0;
}
.terms-body {
  display: flex;
  height: calc(100% - 64px - 60px); /* header + footer */
  overflow: hidden;
}
.terms-toc {
  width: 200px;
  overflow-y: auto;
  flex-shrink: 0;
}
.terms-toc a {
  display: block;
  padding: 5px 0;
  color: #007bff;
  text-decoration: none;
}
.terms-toc a:hover {
  text-decoration: underline;
}
.terms-content {
  overflow-y: auto;
  padding-left: 15px;
}
.terms-footer {
  position: sticky;
  bottom: 0;
  background: #fff;
}
</style>

<!-- JS for Slide Panel -->
<script>
$(document).ready(function(){
  // Open slide panel
  $('#openTermsPanel').on('click', function(e){
    e.preventDefault();
    $('#termsPanel').addClass('show');
  });
  // Close slide panel
  $('#closeTermsPanel').on('click', function(){
    $('#termsPanel').removeClass('show');
  });
  // Accept terms
  $('#acceptTermsBtn').on('click', function(){
    $('input[name="agree_terms"]').prop('checked', true);
    $('#termsPanel').removeClass('show');
  });
  // Smooth scroll TOC
  $('.terms-toc a').on('click', function(e){
    e.preventDefault();
    var target = $(this).attr('href');
    $('#termsContent').animate({ scrollTop: $(target).position().top + $('#termsContent').scrollTop() }, 400);
  });
});
</script>