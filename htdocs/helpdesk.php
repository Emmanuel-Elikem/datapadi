

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>DataPadi Help Desk</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        :root {
            /* Enhanced Palette & Variables */
            --primary: #FFC107; /* Vibrant Yellow */
            --primary-dark: #E0A800;
            --primary-light: #FFF3CD;
            --secondary: #007BFF; /* Accent Blue */
            --secondary-dark: #0056b3;
            --success: #28a745; /* Green for completion */
            --success-dark: #1e7e34;
            --success-light: #d4edda;
            --error: #dc3545;
            --error-light: #f8d7da;
            --text-dark: #212529;
            --text-light: #6c757d;
            --text-white: #ffffff;
            --bg-main: url('image/mtn-bg.jpg');
            --bg-card: rgba(255, 255, 255, 0.95);
            --border-color: #dee2e6;
            --border-color-light: #e9ecef;
            --progress-line-default: #ced4da;

            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 10px rgba(0,0,0,0.08);
            --border-radius-md: 1.25rem;
            --border-radius-lg: 1.25rem;
            --transition-speed: 0.3s;
            --transition-speed-fast: 0.2s;
        }

        /* --- Base & Container --- */
        * { box-sizing: border-box; margin:0; padding:0; font-family: 'Poppins', sans-serif; }
        html { scroll-behavior: smooth; }
        body { background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), var(--bg-main) center/cover; color: var(--text-dark); padding: 1rem; display: flex; justify-content: center; align-items: flex-start; line-height: 1.6; border-radius: var(--border-radius-lg);
}
        .container {
            max-width: 650px; /* Consistent width */
            margin: 2rem auto;
            background: var(--bg-card);
            backdrop-filter: blur(5px); /* Subtle blur effect for background */
            border-radius: var(--border-radius-lg);
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        /* --- Header (Optional - can be simpler) --- */
        header {
            background: var(--primary);
            color: var(--text-dark); /* Darker text on yellow */
            padding: 1.2rem;
            text-align: center;
            font-size: 1.4rem;
            font-weight: 600;
            border-bottom: 1px solid var(--border-color-light);
        }

        /* --- Enhanced Progress Bar --- */
        .progress-bar-container { padding: 1.5rem 1rem; background-color: var(--primary-light); border-bottom: 1px solid var(--border-color-light); }
        .progress-bar { display: flex; justify-content: space-between; align-items: flex-start; position: relative; width: 100%; padding: 0 5%; }
        .progress-step { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; text-align: center; width: 60px; }
        .step-icon-wrapper { width: 40px; height: 40px; border-radius: 50%; background-color: var(--border-color); color: var(--text-light); display: flex; justify-content: center; align-items: center; font-size: 1rem; font-weight: 600; margin-bottom: 0.4rem; border: 3px solid var(--bg-card); transition: background-color var(--transition-speed) ease, color var(--transition-speed) ease; position: relative; z-index: 3; }
        .step-label { font-size: 0.75rem; font-weight: 500; color: var(--text-light); transition: color var(--transition-speed) ease; }
        .progress-step.active .step-icon-wrapper { background-color: var(--primary); color: var(--text-white); }
        .progress-step.active .step-label { color: var(--primary-dark); font-weight: 600; }
        .progress-step.completed .step-icon-wrapper { background-color: var(--success); color: var(--text-white); }
        .progress-step.completed .step-label { color: var(--success); }
        .progress-step:not(:last-child)::after { content: ''; position: absolute; top: 20px; left: 50%; width: 100%; height: 4px; background-color: var(--progress-line-default); transform: translateY(-50%); z-index: 1; transition: background-color var(--transition-speed) ease; }
        .progress-step.completed::after { background-color: var(--success); }

        /* --- Step Styling & Transitions --- */
        .wizard-content { position: relative; min-height: 350px; /* Adjust as needed */}
        .step {
            padding: 2rem;
            /* Absolute positioning for transition */
            position: absolute;
            top: 0; left: 0; width: 100%;
            opacity: 0;
            visibility: hidden;
            transform: translateX(25px);
            transition: opacity var(--transition-speed) ease,
                        visibility var(--transition-speed) ease,
                        transform var(--transition-speed) ease;
        }
        .step.active {
            opacity: 1;
            visibility: visible;
            transform: translateX(0);
            position: relative; /* Take up space when active */
        }
         .step.leaving-forward { transform: translateX(-25px); position:absolute; }
         .step.leaving-backward { transform: translateX(25px); position:absolute; }

        /* Step Titles/Subtitles */
        .step-title { font-size: 1.5rem; font-weight: 600; color: var(--text-dark); margin-bottom: 0.5rem; text-align: center; }
        .step-subtitle { font-size: 1rem; color: var(--text-light); margin-bottom: 2rem; text-align: center; }

        /* --- Form Fields --- */
        .field { margin-bottom: 1.5rem; position: relative; }
        .field label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.9rem;}
        /* Using form-input class for consistency */
        .field .form-input, .field select, .field textarea {
            width: 100%; padding: 0.9rem 1rem 0.9rem 1rem; /* Standard padding */
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-md);
            font-size: 1rem; color: var(--text-dark);
            background-color: var(--bg-main);
            transition: border-color var(--transition-speed) ease, box-shadow var(--transition-speed) ease;
        }
         /* Add icon padding if using icons */
         .field .input-with-icon { padding-left: 3rem; }
         .field .input-icon { position: absolute; left: 15px; top: calc(50% + 10px); transform: translateY(-50%); color: var(--text-light); font-size: 1rem; pointer-events: none; }

        .field .form-input:focus, .field select:focus, .field textarea:focus {
            outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-light); background-color: var(--bg-card);
        }
        .field textarea { resize: vertical; min-height: 100px; padding-top: 0.9rem;}
        .error { color: var(--error); font-size: 0.8rem; display: none; margin-top: 0.3rem; font-weight:500; }

        /* --- Issue Selection Cards --- */
        .issue-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .issue-card {
            background: var(--bg-card); border: 2px solid var(--border-color-light);
            border-radius: var(--border-radius-md); padding: 1.5rem 1rem;
            display: flex; flex-direction: column; align-items: center; gap: 0.75rem;
            cursor: pointer; transition: transform var(--transition-speed-fast) ease, box-shadow var(--transition-speed-fast) ease, border-color var(--transition-speed-fast) ease;
            text-align: center;
        }
        .issue-card i { font-size: 1.75rem; color: var(--primary); margin-bottom: 0.5rem; }
        .issue-card span { font-weight: 600; color: var(--text-dark); font-size: 0.95rem; }
        .issue-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: var(--primary-light); }
        .issue-card.selected { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-light); background: var(--primary-light); transform: scale(1.02); }

        /* --- Solution & Feedback --- */
        .solution-area { background: #ffdb70; border-radius: var(--border-radius-md); padding: 1.5rem; border: 1px solid var(--border-color-light); margin-bottom: 1.5rem; }
        .solution-area h4 { margin-bottom: 1rem; font-weight: 600; color: var(--primary-dark); }
        .solution-steps { list-style: none; padding-left: 0; }
        .solution-steps li { margin-bottom: 0.75rem; display: flex; align-items: flex-start; gap: 0.75rem; font-size: 0.95rem; }
        .solution-steps li i { color: var(--success); margin-top: 0.2em; font-size: 0.9em; }
        .solution-feedback { margin-top: 2rem; text-align: center; }
        .feedback-question { font-weight: 500; margin-bottom: 1rem; color: var(--text-dark); }
        .feedback-buttons button { margin: 0 0.5rem; } /* Inline buttons */

        /* --- WhatsApp Section --- */
        .whatsapp-box { background: var(--success-light); border: 1px solid var(--success); border-radius: var(--border-radius-md); padding: 1.5rem; color: var(--text-dark); font-size: 0.9rem; margin-bottom: 1.5rem; }
        .whatsapp-box strong { color: var(--success-dark); }
        .whatsapp-contact-area { text-align: center; margin-top: 1rem; }
        .whatsapp-contact-area p { margin-bottom: 1rem; color: var(--text-light); }

        /* --- Buttons --- */
        .buttons { display: flex; justify-content: space-between; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color-light);}
        .btn { padding: 0.8rem 1.8rem; border: none; border-radius: var(--border-radius-md); font-size: 0.95rem; font-weight: 600; cursor: pointer; transition: background-color var(--transition-speed) ease, transform var(--transition-speed-fast) ease, box-shadow var(--transition-speed-fast) ease; display: inline-flex; align-items: center; gap: 0.5rem; }
        /* Distinct Button Styles */
        .btn-next { background: var(--secondary); color: var(--text-white); }
        .btn-next:hover { background: var(--secondary-dark); transform: translateY(-2px); box-shadow: var(--shadow-sm); }
        .btn-prev { background: var(--border-color-light); color: var(--text-light); }
        .btn-prev:hover { background: var(--border-color); color: var(--text-dark); transform: translateY(-2px); }
        .btn-success { background: var(--success); color: var(--text-white); }
        .btn-success:hover { background: var(--success-dark); transform: translateY(-2px); box-shadow: var(--shadow-sm); }
        .btn-whatsapp { background: #25D366; color: var(--text-white); } /* WhatsApp Green */
        .btn-whatsapp:hover { background: #128C7E; transform: translateY(-2px); }

        /* --- Final Confirmation Step --- */
        .final-confirmation { text-align: center; padding: 3rem 1rem; }
        .final-confirmation i { font-size: 3rem; color: var(--success); margin-bottom: 1rem; display: block; }
        .final-confirmation h3 { font-size: 1.4rem; color: var(--text-dark); margin-bottom: 0.5rem; }
        .final-confirmation p { color: var(--text-light); margin-bottom: 1.5rem; }
        .final-confirmation a.btn { text-decoration: none; } /* Ensure links styled as buttons look right */

        /* --- Responsive --- */
        @media(max-width: 640px){
             body { padding: 0.5rem; }
            .container { margin-top: 1rem; margin-bottom: 1rem; border-radius: 0; }
            .progress-bar-container { padding: 1rem 0.5rem; }
            .progress-bar { padding: 0; }
            .step-icon-wrapper { width: 35px; height: 35px; font-size: 0.9rem;}
            .step-label { display: none; }
            .progress-step:not(:last-child)::after { top: 17.5px; }
            .step { padding: 1.5rem; }
            .step-title { font-size: 1.3rem; }
            .step-subtitle { font-size: 0.9rem; margin-bottom: 1.5rem;}
            .issue-list { grid-template-columns: 1fr; }
            .buttons { flex-direction: column-reverse; gap: 0.75rem; } /* Stack buttons */
            .btn { width: 100%; justify-content: center; }
            .buttons > div:first-child { display: none; } /* Hide empty div spacer */
             .feedback-buttons button { margin: 0.5rem 0; } /* Stack feedback buttons */
        }
    </style>
</head>
<body>
    <div class="container">
        <header>DataPadi Help Desk</header>

        <div class="progress-bar-container">
            <div class="progress-bar" id="progressBar">
                <div class="progress-step active" data-step="1">
                    <div class="step-icon-wrapper"><i class="fas fa-user"></i></div>
                    <div class="step-label">Identify</div>
                </div>
                <div class="progress-step" data-step="2">
                    <div class="step-icon-wrapper"><i class="fas fa-question"></i></div>
                    <div class="step-label">Issue</div>
                </div>
                <div class="progress-step" data-step="3">
                    <div class="step-icon-wrapper"><i class="fas fa-wrench"></i></div>
                    <div class="step-label">Solution</div>
                </div>
                <div class="progress-step" data-step="4"> <div class="step-icon-wrapper"><i class="fab fa-whatsapp"></i></div>
                    <div class="step-label">Contact</div>
                </div>
            </div>
        </div>

        <div class="wizard-content">

            <div class="step active" id="step1" data-step-number="1">
                <h2 class="step-title">Welcome! Let's Get Started</h2>
                <p class="step-subtitle">Please provide your phone number and network.</p>
                <div class="field">
                    <label for="phone">Your 10-digit Phone Number</label>
                    <span class="input-icon"><i class="fas fa-mobile-alt"></i></span>
                    <input type="tel" id="phone" class="form-input input-with-icon" maxlength="10" placeholder="e.g., 0241234567" />
                    <div class="error" id="err-phone">Enter a valid 10-digit number starting with 0.</div>
                </div>
                <div class="field">
                    <label for="network">Network Operator</label>
                     <span class="input-icon"><i class="fas fa-signal"></i></span>
                    <select id="network" class="form-input input-with-icon"> <option value="" disabled selected>Select your operator</option>
                        <option value="MTN">MTN Ghana</option>
                        <option value="AirtelTigo">AirtelTigo</option>
                        <option value="Telecel">Telecel Ghana</option>
                    </select>
                    <div class="error" id="err-network">Please select your network.</div>
                </div>
                <div class="buttons">
                    <div></div> <button class="btn btn-next" onclick="goNext(1)">Next: Select Issue <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <div class="step" id="step2" data-step-number="2">
                <h2 class="step-title">What can we help with?</h2>
                <p class="step-subtitle">Choose the category that best matches your problem.</p>
                <div class="issue-list">
                    <div class="issue-card" data-issue="Payment Issue">
                       <i class="fas fa-credit-card fa-lg"></i><span>Payment Problem</span>
                    </div>
                    <div class="issue-card" data-issue="Data Not Received">
                       <i class="fas fa-clock fa-lg"></i><span>Data Not Received</span>
                    </div>
                    <div class="issue-card" data-issue="Incorrect Bundle">
                       <i class="fas fa-box-open fa-lg"></i><span>Wrong Data Package</span>
                    </div>
                    <div class="issue-card" data-issue="General Question">
                       <i class="fas fa-question-circle fa-lg"></i><span>General Question</span>
                    </div>
                </div>
                 <div class="error" id="err-issue" style="text-align: center; margin-top: 1rem;">Please select an issue category.</div>
                <div class="buttons">
                    <button class="btn btn-prev" onclick="goPrev(2)"><i class="fas fa-arrow-left"></i> Back</button>
                    <button class="btn btn-next" onclick="goNext(2)">Next: Try Solutions <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <div class="step" id="step3" data-step-number="3">
                <h2 class="step-title">Let's Try This First</h2>
                 <p class="step-subtitle">Here are common fixes for '<span id="issue-title-placeholder" style="font-weight: 600;"></span>'</p>
                <div class="solution-area" id="solutions"> </div>

                 <div class="solution-feedback">
                     <p class="feedback-question">Did these steps resolve your issue?</p>
                     <div class="feedback-buttons">
                         <button class="btn btn-success" id="btn-solved"><i class="fas fa-check"></i> Yes, it's fixed!</button>
                         <button class="btn btn-next" id="btn-not-solved"><i class="fas fa-times"></i> No, I need more help</button> </div>
                 </div>

                <div class="buttons"> <button class="btn btn-prev" onclick="goPrev(3)"><i class="fas fa-arrow-left"></i> Back</button>
                    <div></div> </div>
            </div>

            <div class="step" id="step4" data-step-number="4">
                 <h2 class="step-title">Okay, Tell Us More</h2>
                 <p class="step-subtitle">Provide details below, then click to open WhatsApp.</p>

                 <div class="whatsapp-box" id="whatsappSummary">
                     </div>

                 <div class="field" style="margin-top: 1.5rem;"> <label for="details">Describe the issue (include Transaction ID if available)</label>
                    <textarea id="details" rows="4" placeholder="E.g., I paid for 5 GB but got 2 GB..."></textarea>
                 </div>

                 <div class="whatsapp-contact-area">
                    <p>Click below to open WhatsApp with your details pre-filled.</p>
                    <a id="waLink" target="_blank" class="btn btn-whatsapp">
                        <i class="fab fa-whatsapp fa-lg"></i> Chat on WhatsApp
                    </a>
                 </div>

                <div class="buttons">
                    <button class="btn btn-prev" onclick="goPrev(4)"><i class="fas fa-arrow-left"></i> Back</button>
                    <div></div> </div>
            </div>

            <div class="step" id="step-final" data-step-number="final">
                  <div class="final-confirmation">
                       <i class="fas fa-check-circle"></i>
                       <h3>Great! We're Glad We Could Help.</h3>
                       <p>Your issue seems to be resolved. If anything else comes up, feel free to start over.</p>
                       <a href="/" class="btn btn-next"> <i class="fas fa-shopping-cart"></i> Make Another Purchase
                       </a>
                  </div>
                  </div>

        </div> </div> <script>
        const yourWhatsAppNumber = "+233596067127"; // IMPORTANT: Replace!
        const totalVisibleSteps = 4; // Number of steps shown in progress bar
        let currentStep = 1;
        // Use the state structure from the provided code
        let state = { phone:'', network:'', issue:'', details:'' };

        // --- DOM References ---
        const steps = document.querySelectorAll('.step');
        const progressSteps = document.querySelectorAll('.progress-step');
        const phoneInput = document.getElementById('phone');
        const networkSelect = document.getElementById('network');
        const issueCards = document.querySelectorAll('.issue-card');
        const detailsTextarea = document.getElementById('details');
                      // Add this after the textarea initialization
const detailsCounter = document.createElement('div');
detailsCounter.style = 'font-size: 0.8rem; color: var(--text-light); text-align: right; margin-top: 0.5rem;';
detailsTextarea.parentNode.appendChild(detailsCounter);

detailsTextarea.addEventListener('input', function() {
    state.details = this.value.trim();
    prepareWhatsAppSummaryAndLink();
    detailsCounter.textContent = `${this.value.length}/500 characters`;
});
        const waLink = document.getElementById('waLink');
        const solutionsDiv = document.getElementById('solutions');
        const issueTitlePlaceholder = document.getElementById('issue-title-placeholder');
        const whatsappSummaryDiv = document.getElementById('whatsappSummary');
        const btnSolved = document.getElementById('btn-solved');
        const btnNotSolved = document.getElementById('btn-not-solved');

        // --- Helper Functions ---
        function showError(elementId, message = null) {
             const errorElement = document.getElementById(elementId);
             if (errorElement) {
                 if(message) errorElement.textContent = message;
                 errorElement.style.display = 'block';
                 // Focus the related input if applicable
                 const inputId = elementId.replace('err-', '');
                 document.getElementById(inputId)?.focus();
             }
         }

        function hideError(elementId) {
             const errorElement = document.getElementById(elementId);
             if (errorElement) errorElement.style.display = 'none';
        }

        function updateProgress(stepNum) {
            // console.log(`Updating progress for step: ${stepNum}`); // DEBUG
            progressSteps.forEach((el, i) => {
                const stepIndex = i + 1; // Progress steps are 1-based index
                el.classList.remove('active', 'completed');
                if (stepIndex < stepNum) {
                    el.classList.add('completed');
                } else if (stepIndex === stepNum) {
                    el.classList.add('active');
                }
            });
        }

        function showStep(stepNumToShow) {
             // console.log(`Attempting to show step: ${stepNumToShow}`); // DEBUG
             const currentActiveStep = document.querySelector('.step.active');
             const targetStepElement = document.getElementById(`step${stepNumToShow}`);
             const isFinalStep = stepNumToShow === 'final'; // Check if it's the special final step

             if (!targetStepElement && !isFinalStep) {
                  console.error(`Target step element "step${stepNumToShow}" not found.`);
                  return;
             }
              if (isFinalStep && !document.getElementById('step-final')) {
                  console.error(`Target step element "step-final" not found.`);
                  return;
              }

             const targetId = isFinalStep ? 'step-final' : `step${stepNumToShow}`;
             const targetEl = document.getElementById(targetId);

             if (currentActiveStep) {
                 const currentStepNumber = parseInt(currentActiveStep.dataset.stepNumber || 0);
                 const targetStepNumberNumeric = isFinalStep ? totalVisibleSteps + 1 : stepNumToShow; // Assign a number for comparison

                 if (targetStepNumberNumeric > currentStepNumber) {
                     currentActiveStep.classList.add('leaving-forward');
                 } else if (targetStepNumberNumeric < currentStepNumber) {
                     currentActiveStep.classList.add('leaving-backward');
                 }
                 // Let CSS handle removal via animation end or timeout if needed
                 currentActiveStep.classList.remove('active');
             }

             // Use setTimeout to ensure class changes apply for transition
             setTimeout(() => {
                 // Remove leaving classes after potential transition start
                 document.querySelectorAll('.step').forEach(s => s.classList.remove('leaving-forward', 'leaving-backward'));

                 // Activate target step
                 if (targetEl) {
                     targetEl.classList.add('active');
                 }

                 // Update state variable - use numeric value even for 'final'
                 currentStep = isFinalStep ? totalVisibleSteps + 1 : stepNumToShow;

                 // Update progress bar only for visible steps
                 updateProgress(isFinalStep ? totalVisibleSteps : currentStep);

                  // Scroll to top
                  document.querySelector('.container').scrollIntoView({ behavior: 'smooth', block: 'start' });

             }, 1000); // Short delay for transition rendering
        }


        // --- Event Listeners ---

        // Add real-time textarea monitoring
detailsTextarea.addEventListener('input', function() {
    state.details = this.value.trim();
    prepareWhatsAppSummaryAndLink(); // Update the preview and link immediately
});

        // Network Auto-Detect (from original code)
        phoneInput.addEventListener('input', e => {
            let v = e.target.value.replace(/\D/g,''); // Allow only digits
            e.target.value = v; // Update input field
            hideError('err-phone'); // Hide error on input
            if(v.length >= 3){
                const p = v.slice(0,3);
                let net = '';
                // Simplified/updated prefixes (verify these are still accurate)
                if(['024', '054', '055', '059', '025', '053'].includes(p)) net = 'MTN';
                if(['026', '056', '027', '057'].includes(p)) net = 'AirtelTigo'; // AirtelTigo
                if(['020', '050'].includes(p)) net = 'Telecel'; // Telecel
                if(net) networkSelect.value = net;
            }
        });
        networkSelect.addEventListener('change', () => hideError('err-network')); // Hide network error on change

        // Issue Cards Selection (from original code, adapted slightly)
        issueCards.forEach(card => {
            card.addEventListener('click', () => {
                issueCards.forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                state.issue = card.dataset.issue; // Update state directly
                // console.log(`DEBUG: Issue set to: ${state.issue}`); // DEBUG
                hideError('err-issue'); // Hide error on selection
            });
        });

        // Step 3 Feedback Buttons
        if (btnSolved) {
            btnSolved.addEventListener('click', () => {
                // console.log("DEBUG: 'Yes, it's fixed!' clicked."); // DEBUG
                showStep('final'); // Show the final confirmation step
            });
        }
        if (btnNotSolved) {
           btnNotSolved.addEventListener('click', () => {
                // console.log("DEBUG: 'No, I need more help' clicked."); // DEBUG
                // This button click effectively acts like goNext(3)
                goNext(3);
           });
        }


        // --- Navigation Functions ---

        function goNext(currentStepNum) {
    // ... existing validation code ...

    if (currentStepNum === 3) {
        // Force initial update when entering step 4
        prepareWhatsAppSummaryAndLink();
    }
    
           // Validation
            let isValid = true;
            if(currentStepNum === 1) {
                const ph = phoneInput.value;
                const nw = networkSelect.value;
                hideError('err-phone'); hideError('err-network'); // Reset errors
                if(ph.length !== 10 || !ph.startsWith('0')) {
                    showError('err-phone'); isValid = false;
                }
                if(!nw) {
                    showError('err-network'); isValid = false;
                }
                if(isValid) {
                    state.phone = ph; state.network = nw;
                }
            }
            else if(currentStepNum === 2) {
                hideError('err-issue'); // Reset
                if(!state.issue) {
                    showError('err-issue'); isValid = false;
                }
                if (isValid) {
                     renderSolutions(); // Render solutions before showing step 3
                }
            }
            else if(currentStepNum === 3) {
                 // Logic moved to btnNotSolved listener, but keep validation if needed
                 // This transition happens *only* if "No, I need more help" is clicked
                 // Prepare WhatsApp summary before showing step 4
                 prepareWhatsAppSummaryAndLink();
            }
             // Step 4 has no "Next" button, only back and WhatsApp link

            if (isValid) {
                 // console.log(`DEBUG: goNext(${currentStepNum}) - validation OK, showing step ${currentStepNum + 1}`); // DEBUG
                 showStep(currentStepNum + 1);
            } else {
                 // console.log(`DEBUG: goNext(${currentStepNum}) - validation FAILED`); // DEBUG
            }
        }

        function goPrev(currentStepNum) {
             // console.log(`DEBUG: goPrev(${currentStepNum}) - showing step ${currentStepNum - 1}`); // DEBUG
            if (currentStepNum > 1) {
                 // Hide errors on the step we are going back TO
                 const prevStepId = `step${currentStepNum - 1}`;
                 document.querySelectorAll(`#${prevStepId} .error`).forEach(el => el.style.display = 'none');
                showStep(currentStepNum - 1);
            }
        }

        // --- Content Rendering ---

        function renderSolutions() {
            if (!state.issue) return; // Should not happen if validation works
            issueTitlePlaceholder.textContent = state.issue; // Update placeholder in step 3 title
            const sols = {
    'Payment Issue': [
        ' Verify payment number matches your SIM',
        ' For MoMo: Dial *170# → "My Approvals" to approve pending transactions',
        ' For Bank/Card: Check transaction in your banking app',
        ' Be sure you have sufficient MoMo/bank balance',
        ' Wait 5 minutes then refresh and try again',
        '❗ Tip: Always keep transaction ID for reference'
    ],
    'Data Not Received': [
        ' Toggle airplane mode on/off',
        ' Confirm number to receive data is correct',
        ' Check data balance',
        ' Wait 15 mins - network delays happen!',
        ' Our working hours is 8AM-8PM daily'
    ],
    'Incorrect Bundle': [
        ' Check SMS confirmation for bundle details',
        ' Verify bundle selection before payment',
        ' Compare with pricing page',
        ' Select Next to continue to contact support'
    ],
    'General Question': [
        ' Check pricing page',
        ' Our working hours is 8AM-8PM daily',
        ' Reach out for more info'
    ],
    'Payment Failed': [
        ' Be sure you have sufficient MoMo/bank balance',
        ' Check internet connection strength',
        ' Look for approval notifications',
        ' Note any error messages shown',
        ' Wait 5 minutes and retry',
        '❗ Urgent: Contact us if money was deducted!'
    ]
};
            const issueSolutions = sols[state.issue] || ['Please provide details about your issue.']; // Fallback

            solutionsDiv.innerHTML = issueSolutions.map(s =>
                // Use improved list format
                `<li class="solution-steps"><i class="fas fa-check-circle"></i><span>${s}</span></li>`
            ).join('');
        }

        function prepareWhatsAppSummaryAndLink() {
    // Always get fresh value from textarea
    const currentDetails = detailsTextarea.value.trim();
    
    // Prepare Summary for display
    whatsappSummaryDiv.innerHTML = `
        <h4 style="text-align: center; margin-bottom: 1rem; color: var(--success-dark);">Summary to be Sent:</h4>
        <p><strong>Phone:</strong> ${state.phone || 'N/A'}</p>
        <p><strong>Network:</strong> ${state.network || 'N/A'}</p>
        <p><strong>Issue:</strong> ${state.issue || 'N/A'}</p>
        ${currentDetails ? 
            `<p><strong>Details:</strong> ${currentDetails.substring(0, 100)}${currentDetails.length > 100 ? '...' : ''}</p>` : 
            '<p><i>No additional details provided.</i></p>'}
    `;

    // Prepare WhatsApp Link with live data
    const msg = `*DataPadi Help Request*\n--------------------\n*Phone:* ${state.phone}\n*Network:* ${state.network}\n*Issue:* ${state.issue}\n--------------------\n*Details:*\n${currentDetails || '(No details provided)'}`;
    const url = `https://wa.me/${yourWhatsAppNumber.replace('+', '')}?text=${encodeURIComponent(msg)}`;
    waLink.href = url;
}

        // --- Initialisation ---
        // Show Step 1 on load (handled by default active class)
        updateProgress(1); // Set initial progress bar state
        // console.log("DEBUG: Enhanced Help Wizard Initialized."); // DEBUG

    </script>
</body>
</html>