/* authentication variables*/
let currentRole = 'student';
let currentMode = 'login';
let authModal = null;

/*function to call when auth modal is needed*/
function getAuthModal() {
    if (!authModal) {
        const modalEl = document.getElementById('authModal');
        if (!modalEl) return null;
        authModal = new bootstrap.Modal(modalEl);
    }
    return authModal;
}

/*switch roles of auth modal*/
function setRole(role) {
    currentRole = role;
    document.getElementById('inputRole').value = role;

    document.getElementById('btn-student')
        ?.classList.toggle('active', role === 'student');

    document.getElementById('btn-teacher')
        ?.classList.toggle('active', role === 'teacher');
}

/*switch modes of auth modal -- login or sigup */
function toggleMode(forceSignup = false) {
    if (forceSignup) {
        currentMode = 'signup';
    } else {
        currentMode = currentMode === 'login' ? 'signup' : 'login';
    }

    document.getElementById('inputMode').value = currentMode;
    const signup = currentMode === 'signup';

    document.getElementById('formTitle').textContent =
        signup ? 'Join HellenicTech' : 'Welcome Back';

    document.getElementById('submitBtnText').textContent =
        signup ? 'Create Account' : 'Sign In';

    document.getElementById('toggleText').textContent =
        signup ? 'Already have an account?' : 'New to HellenicTech?';

    document.getElementById('toggleLink').textContent =
        signup ? 'Back to Login' : 'Apply for Access / Sign Up';

    document.getElementById('nameField')
        .classList.toggle('d-none', !signup);

    document.getElementById('specialCodeField')
        .classList.toggle('d-none', !signup);
}

/*on click events for global use*/
document.addEventListener('click', (e) => {

    //opn modal auth
    if (e.target.closest('#openAuth')) {
        getAuthModal()?.show();
    }

    //role switch
    if (e.target.closest('#btn-student')) setRole('student');
    if (e.target.closest('#btn-teacher')) setRole('teacher');

    //mode switch
    if (e.target.closest('#toggleLink')) toggleMode();
});

/*auto opens signup modal on load*/
document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('m') === 'signup') {
        const interval = setInterval(() => {
            const modal = getAuthModal();
            if (modal) {
                toggleMode(true);
                modal.show();
                clearInterval(interval);
            }
        }, 50);
    }
});

/*to subbmit form and handle response */
document.addEventListener('submit', async (e) => {
    if (!e.target.matches('#authForm')) return;

    e.preventDefault();

    const form = e.target;
    const data = new FormData(form);

    const errBox = document.getElementById('alertErrors');
    const okBox = document.getElementById('alertSuccess');

    errBox.classList.add('d-none');
    okBox.classList.add('d-none');

    try {
        const res = await fetch('/prototype/backend/auth.php', {
            method: 'POST',
            body: data
        });

        const json = await res.json();

        if (!json.success) {
            errBox.innerHTML = `
                <ul class="mb-0">
                    ${json.errors.map(e => `<li>${e}</li>`).join('')}
                </ul>`;
            errBox.classList.remove('d-none');
            return;
        }

        // Signup success
        if (json.message) {
            okBox.textContent = json.message;
            okBox.classList.remove('d-none');
            toggleMode(false);
            return;
        }

        // Login success
        window.location.href = '/prototype/dashboard.html';

    } catch {
        errBox.textContent = 'Unexpected server error.';
        errBox.classList.remove('d-none');
    }
});
