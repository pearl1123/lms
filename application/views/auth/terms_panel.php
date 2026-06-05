<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div id="termsPanel" class="terms-panel bg-white shadow-lg" aria-hidden="true">
  <div class="terms-header d-flex justify-content-between align-items-center p-3 border-bottom">
    <h5 class="m-0">kaBAGA Academy Terms &amp; Policies</h5>
    <button type="button" id="closeTermsPanel" class="btn-close" aria-label="Close"></button>
  </div>
  <div class="terms-body d-flex">
    <div class="terms-toc border-end p-3">
      <ul class="list-unstyled mb-0">
        <li><a href="#acceptance">1. Acceptance</a></li>
        <li><a href="#eligibility">2. Eligibility</a></li>
        <li><a href="#account">3. Account Security</a></li>
        <li><a href="#use">4. Acceptable Use</a></li>
        <li><a href="#ip">5. Intellectual Property</a></li>
        <li><a href="#privacy">6. Data Privacy</a></li>
        <li><a href="#monitoring">7. System Monitoring</a></li>
        <li><a href="#completion">8. Completion &amp; Certifications</a></li>
        <li><a href="#conduct">9. Code of Conduct</a></li>
        <li><a href="#suspension">10. Account Suspension</a></li>
        <li><a href="#updates">11. Policy Updates</a></li>
        <li><a href="#contact">12. Contact Info</a></li>
      </ul>
    </div>
    <div class="terms-content flex-grow-1 p-3" id="termsContent">
      <section id="acceptance">
        <h6 class="fw-bold">1. Acceptance of Terms</h6>
        <p>By accessing or using kaBAGA Academy, the Learning Management System (LMS) for the Lung Center of the Philippines, you acknowledge that you have read, understood, and agree to comply with these Terms and Policies. If you do not agree with any part, you must immediately discontinue use.</p>
      </section>
      <section id="eligibility">
        <h6 class="fw-bold">2. User Eligibility</h6>
        <p>Access is limited to authorized employees, trainees, and personnel of the Lung Center of the Philippines. Users must provide accurate information during registration and must not share credentials.</p>
      </section>
      <section id="account">
        <h6 class="fw-bold">3. Account Security</h6>
        <p>Each user is responsible for maintaining confidentiality of login credentials. Unauthorized use or disclosure of account information is strictly prohibited. Any activity performed under your account is your responsibility.</p>
      </section>
      <section id="use">
        <h6 class="fw-bold">4. Acceptable Use Policy</h6>
        <ul>
          <li>Use the platform solely for authorized learning and training activities.</li>
          <li>Do not upload malware, viruses, or malicious files.</li>
          <li>Do not attempt to access restricted system areas without permission.</li>
          <li>Do not share confidential content outside the organization.</li>
          <li>Compliance with the Lung Center's IT policies is mandatory.</li>
        </ul>
      </section>
      <section id="ip">
        <h6 class="fw-bold">5. Intellectual Property</h6>
        <p>All content, including course materials, videos, documents, and images, are protected by intellectual property laws. Use is limited to internal training.</p>
      </section>
      <section id="privacy">
        <h6 class="fw-bold">6. Data Privacy</h6>
        <p>Personal data collected will be processed according to the Philippine Data Privacy Act (RA 10173) and the Lung Center's internal privacy policies.</p>
      </section>
      <section id="monitoring">
        <h6 class="fw-bold">7. System Monitoring</h6>
        <p>User activities may be logged and monitored for security, compliance, and system improvement purposes.</p>
      </section>
      <section id="completion">
        <h6 class="fw-bold">8. Course Completion &amp; Certifications</h6>
        <p>Certificates are issued based on completion of lessons, assessments, and instructor validations.</p>
      </section>
      <section id="conduct">
        <h6 class="fw-bold">9. Code of Conduct</h6>
        <ul>
          <li>Maintain professional behavior on the platform.</li>
          <li>No harassment, offensive language, or inappropriate content.</li>
          <li>Respect confidentiality of patient-related materials.</li>
        </ul>
      </section>
      <section id="suspension">
        <h6 class="fw-bold">10. Account Suspension</h6>
        <p>Accounts may be suspended or terminated for violating these Terms, security breaches, or unauthorized activities.</p>
      </section>
      <section id="updates">
        <h6 class="fw-bold">11. Policy Updates</h6>
        <p>Terms and Policies may be updated periodically. Continued use constitutes acceptance of the updated terms.</p>
      </section>
      <section id="contact">
        <h6 class="fw-bold">12. Contact Information</h6>
        <p>Contact the Management Information Systems Division or the LMS Administrator at the Lung Center of the Philippines.</p>
      </section>
    </div>
  </div>
  <div class="terms-footer border-top p-3 d-flex justify-content-end">
    <button type="button" id="agree_terms_button" class="btn btn-primary">I Agree to the Terms</button>
  </div>
</div>
<div id="termsPanelBackdrop" class="terms-panel-backdrop" aria-hidden="true"></div>
<style>
  .terms-panel {
    position: fixed;
    top: 0;
    right: 0;
    width: min(92vw, 900px);
    height: 100vh;
    z-index: 1050;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transform: translateX(100%);
    transition: transform 0.35s ease;
  }
  .terms-panel.show { transform: translateX(0); }
  .terms-panel-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    z-index: 1040;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.35s ease;
  }
  .terms-panel-backdrop.show {
    opacity: 1;
    pointer-events: auto;
  }
  .terms-body {
    display: flex;
    flex: 1;
    min-height: 0;
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
    color: #1c4f87;
    text-decoration: none;
  }
  .terms-toc a:hover { text-decoration: underline; }
  .terms-content { overflow-y: auto; }
  .terms-footer { flex-shrink: 0; background: #fff; }
</style>
