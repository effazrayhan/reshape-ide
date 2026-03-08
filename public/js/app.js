class LogicIDE {
    constructor() {
        this.currentLesson = null;
        this.score = 0;
        this.lessons = [];
        this.completedLessons = new Set();
        
        this.currentUser = null;
        this.token = localStorage.getItem('jwt_token');
        
        this.editor = null;
        this.hintEngine = null;
        
        this.elements = {
            lessonList: document.getElementById('lessons-container'),
            lessonTitle: document.getElementById('lesson-title'),
            lessonDifficulty: document.getElementById('lesson-difficulty'),
            problemDescription: document.getElementById('problem-description'),
            output: document.getElementById('output'),
            scoreDisplay: document.getElementById('score-display'),
            modal: document.getElementById('modal'),
            modalTitle: document.getElementById('modal-title'),
            modalMessage: document.getElementById('modal-message'),
            modalScore: document.getElementById('modal-score'),
            modalNextBtn: document.getElementById('modal-next-btn'),
            runBtn: document.getElementById('run-btn'),
            resetBtn: document.getElementById('reset-btn'),
            clearOutputBtn: document.getElementById('clear-output'),
            modalClose: document.querySelector('.modal-close'),
            // Auth elements
            authButtons: document.getElementById('auth-buttons'),
            userMenu: document.getElementById('user-menu'),
            usernameDisplay: document.getElementById('username-display'),
            loginBtn: document.getElementById('login-btn'),
            signupBtn: document.getElementById('signup-btn'),
            adminLoginBtn: document.getElementById('admin-login-btn'),
            adminNav: document.getElementById('admin-nav'),
            logoutBtn: document.getElementById('logout-btn'),
            authModal: document.getElementById('auth-modal'),
            authModalClose: document.querySelector('.auth-modal-close'),
            loginFormContainer: document.getElementById('login-form-container'),
            signupFormContainer: document.getElementById('signup-form-container'),
            loginForm: document.getElementById('login-form'),
            signupForm: document.getElementById('signup-form'),
            switchToSignup: document.getElementById('switch-to-signup'),
            switchToLogin: document.getElementById('switch-to-login'),
            loginError: document.getElementById('login-error'),
            signupError: document.getElementById('signup-error')
        };
        
        this.init();
    }

    async init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupApp());
        } else {
            this.setupApp();
        }
    }

    setupApp() {
        this.editor = new CodeEditor('code-editor');
        this.hintEngine = new HintEngine();
        this.setupEventListeners();
        this.setupAuth();
        this.loadLessons();
    }

    setupAuth() {
        if (this.token) {
            this.fetchCurrentUser();
        }
        
        if (this.elements.loginBtn) {
            this.elements.loginBtn.addEventListener('click', () => this.showAuthModal('login'));
        }
        
        if (this.elements.signupBtn) {
            this.elements.signupBtn.addEventListener('click', () => this.showAuthModal('signup'));
        }
        
        if (this.elements.adminLoginBtn) {
            this.elements.adminLoginBtn.addEventListener('click', () => this.showAdminLogin());
        }
        
        if (this.elements.adminNav) {
            this.elements.adminNav.addEventListener('click', () => this.showAdminPanel());
        }
        
        if (this.elements.logoutBtn) {
            this.elements.logoutBtn.addEventListener('click', () => this.logout());
        }
        
        if (this.elements.authModalClose) {
            this.elements.authModalClose.addEventListener('click', () => this.closeAuthModal());
        }
        
        if (this.elements.authModal) {
            this.elements.authModal.addEventListener('click', (e) => {
                if (e.target === this.elements.authModal) {
                    this.closeAuthModal();
                }
            });
        }
        
        if (this.elements.switchToSignup) {
            this.elements.switchToSignup.addEventListener('click', (e) => {
                e.preventDefault();
                this.showAuthForm('signup');
            });
        }
        
        if (this.elements.switchToLogin) {
            this.elements.switchToLogin.addEventListener('click', (e) => {
                e.preventDefault();
                this.showAuthForm('login');
            });
        }
        
        if (this.elements.loginForm) {
            this.elements.loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }
        
        if (this.elements.signupForm) {
            this.elements.signupForm.addEventListener('submit', (e) => this.handleSignup(e));
        }
    }

    showAuthModal(type) {
        if (!this.elements.authModal) return;
        this.showAuthForm(type);
        this.elements.authModal.classList.add('show');
    }

    closeAuthModal() {
        if (!this.elements.authModal) return;
        this.elements.authModal.classList.remove('show');
        if (this.elements.loginForm) this.elements.loginForm.reset();
        if (this.elements.signupForm) this.elements.signupForm.reset();
        if (this.elements.loginError) this.elements.loginError.textContent = '';
        if (this.elements.signupError) this.elements.signupError.textContent = '';
    }

    showAuthForm(type) {
        if (!this.elements.loginFormContainer || !this.elements.signupFormContainer) return;
        
        if (type === 'signup') {
            this.elements.loginFormContainer.style.display = 'none';
            this.elements.signupFormContainer.style.display = 'block';
        } else {
            this.elements.signupFormContainer.style.display = 'none';
            this.elements.loginFormContainer.style.display = 'block';
        }
    }

    async handleLogin(e) {
        e.preventDefault();
        
        const email = document.getElementById('login-email')?.value;
        const password = document.getElementById('login-password')?.value;
        
        if (!email || !password) return;
        
        try {
            const response = await fetch('/api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.token = data.token;
                this.currentUser = data.user;
                localStorage.setItem('jwt_token', this.token);
                this.updateAuthUI();
                this.closeAuthModal();
                await this.mergeAnonymousProgress();
                await this.loadProgress();
            } else {
                if (this.elements.loginError) {
                    this.elements.loginError.textContent = data.message || 'Login failed';
                }
            }
        } catch (error) {
            console.error('Login error:', error);
            if (this.elements.loginError) {
                this.elements.loginError.textContent = 'Login failed. Please try again.';
            }
        }
    }

    async mergeAnonymousProgress() {
        try {
            const anonId = this.getAnonymousUserId();
            if (!anonId) return;
            
            await fetch('/api/merge-progress.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.token}`
                },
                body: JSON.stringify({ anonymousUserId: anonId })
            });
        } catch (error) {
            console.error('Merge progress error:', error);
        }
    }

    getAnonymousUserId() {
        let anonId = sessionStorage.getItem('anonymous_user_id');
        if (!anonId) {
            anonId = 'anon_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            sessionStorage.setItem('anonymous_user_id', anonId);
        }
        return anonId;
    }

    async handleSignup(e) {
        e.preventDefault();
        
        const username = document.getElementById('signup-username')?.value;
        const email = document.getElementById('signup-email')?.value;
        const password = document.getElementById('signup-password')?.value;
        const confirmPassword = document.getElementById('signup-confirm-password')?.value;
        
        if (!username || !email || !password) return;
        
        if (password !== confirmPassword) {
            if (this.elements.signupError) {
                this.elements.signupError.textContent = 'Passwords do not match';
            }
            return;
        }
        
        try {
            const response = await fetch('/api/signup.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, email, password })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.token = data.token;
                this.currentUser = data.user;
                localStorage.setItem('jwt_token', this.token);
                this.updateAuthUI();
                this.closeAuthModal();
                await this.loadProgress();
            } else {
                if (this.elements.signupError) {
                    this.elements.signupError.textContent = data.message || 'Signup failed';
                }
            }
        } catch (error) {
            console.error('Signup error:', error);
            if (this.elements.signupError) {
                this.elements.signupError.textContent = 'Signup failed. Please try again.';
            }
        }
    }

    async fetchCurrentUser() {
        try {
            const response = await fetch('/api/user.php', {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.currentUser = data.user;
                    this.updateAuthUI();
                    await this.loadProgress();
                } else {
                    this.logout();
                }
            } else {
                this.logout();
            }
        } catch (error) {
            console.error('Fetch user error:', error);
        }
    }

    logout() {
        this.token = null;
        this.currentUser = null;
        this.isAdmin = false;
        localStorage.removeItem('jwt_token');
        this.updateAuthUI();
    }

    // Admin methods
    async showAdminLogin() {
        const email = prompt('Admin Email:');
        if (!email) return;
        
        const password = prompt('Admin Password:');
        if (!password) return;
        
        try {
            const response = await fetch('/api/admin-login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.token = data.token;
                this.currentUser = data.user;
                this.isAdmin = true;
                localStorage.setItem('jwt_token', this.token);
                this.updateAuthUI();
                this.showAdminPanel();
            } else {
                alert(data.message || 'Admin login failed');
            }
        } catch (error) {
            console.error('Admin login error:', error);
            alert('Admin login failed');
        }
    }

    async showAdminPanel() {
        if (!this.isAdmin) {
            alert('Please login as admin first');
            return;
        }
        
        const panel = document.getElementById('admin-panel');
        if (panel) {
            panel.style.display = 'block';
            this.loadAdminUsers();
        }
        
        // Setup close button
        const closeBtn = document.getElementById('close-admin');
        if (closeBtn) {
            closeBtn.onclick = () => {
                panel.style.display = 'none';
            };
        }
        
        // Setup tabs
        const tabs = document.querySelectorAll('.admin-tab');
        tabs.forEach(tab => {
            tab.onclick = () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                const tabName = tab.dataset.tab;
                document.getElementById('admin-users-tab').style.display = tabName === 'users' ? 'block' : 'none';
                document.getElementById('admin-create-lesson-tab').style.display = tabName === 'create-lesson' ? 'block' : 'none';
                
                if (tabName === 'users') {
                    this.loadAdminUsers();
                }
            };
        });
        
        // Setup create lesson form
        const form = document.getElementById('create-lesson-form');
        if (form) {
            form.onsubmit = async (e) => {
                e.preventDefault();
                await this.createLesson();
            };
        }
    }

    async loadAdminUsers() {
        try {
            const response = await this.apiRequest('/api/admin-users.php');
            const data = await response.json();
            
            if (data.success) {
                this.renderAdminUsers(data.users);
            }
        } catch (error) {
            console.error('Load admin users error:', error);
        }
    }

    renderAdminUsers(users) {
        const container = document.getElementById('users-list');
        if (!container) return;
        
        container.innerHTML = users.map(user => `
            <div class="user-card">
                <div class="user-card-header">
                    <strong>${user.username}</strong>
                    <span class="user-card-score">${user.total_score} pts</span>
                </div>
                <div class="user-card-email">${user.email}</div>
                <div class="user-card-progress">
                    Solved: ${user.lessons_completed} lessons
                    ${user.solved_problems ? user.solved_problems.map(p => 
                        '<span class="user-solved-item">' + p.lesson_title + '</span>'
                    ).join('') : ''}
                </div>
            </div>
        `).join('');
    }

    async createLesson() {
        const title = document.getElementById('create-lesson-title')?.value;
        const difficulty = document.getElementById('lesson-difficulty')?.value;
        const description = document.getElementById('create-lesson-description')?.value;
        const starterCode = document.getElementById('create-lesson-starter-code')?.value;
        const solution = document.getElementById('create-lesson-solution')?.value;
        const points = parseInt(document.getElementById('create-lesson-points')?.value) || 100;
        const hintsText = document.getElementById('create-lesson-hints')?.value;
        const testCasesText = document.getElementById('create-lesson-test-cases')?.value;
        
        const hints = hintsText ? hintsText.split('\n').filter(h => h.trim()) : [];
        
        let testCases = [];
        try {
            testCases = testCasesText ? JSON.parse(testCasesText) : [];
        } catch (e) {
            alert('Invalid test cases JSON format');
            return;
        }
        
        try {
            const response = await this.apiRequest('/api/admin-create-lesson.php', {
                method: 'POST',
                body: JSON.stringify({
                    title, difficulty, description, starterCode, solution, points, hints, testCases
                })
            });
            
            const data = await response.json();
            
            const resultDiv = document.getElementById('create-lesson-result');
            if (data.success) {
                resultDiv.innerHTML = '<p style="color: green;">' + data.message + '</p>';
                document.getElementById('create-lesson-form').reset();
            } else {
                resultDiv.innerHTML = '<p style="color: red;">' + (data.message || 'Failed') + '</p>';
            }
        } catch (error) {
            console.error('Create lesson error:', error);
            alert('Failed to create lesson');
        }
    }

    updateAuthUI() {
        if (!this.elements.authButtons || !this.elements.userMenu || !this.elements.usernameDisplay) return;
        
        if (this.currentUser) {
            this.elements.authButtons.style.display = 'none';
            this.elements.userMenu.style.display = 'flex';
            this.elements.usernameDisplay.textContent = this.currentUser.username;
            
            if (this.elements.adminNav) {
                this.elements.adminNav.style.display = this.isAdmin ? 'block' : 'none';
            }
        } else {
            this.elements.authButtons.style.display = 'flex';
            this.elements.userMenu.style.display = 'none';
            if (this.elements.adminNav) {
                this.elements.adminNav.style.display = 'none';
            }
        }
    }

    async apiRequest(url, options = {}) {
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };
        
        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }
        
        return fetch(url, {
            ...options,
            headers
        });
    }

    getProgressUserId() {
        if (this.currentUser) {
            return { id: this.currentUser.id, isAuthenticated: true };
        }
        return { id: this.getAnonymousUserId(), isAuthenticated: false };
    }

    setupEventListeners() {
        if (this.elements.runBtn) {
            this.elements.runBtn.addEventListener('click', () => this.runCode());
        }
        
        if (this.elements.resetBtn) {
            this.elements.resetBtn.addEventListener('click', () => this.resetCode());
        }
        
        if (this.elements.clearOutputBtn) {
            this.elements.clearOutputBtn.addEventListener('click', () => this.clearOutput());
        }
        
        if (this.elements.modalClose) {
            this.elements.modalClose.addEventListener('click', () => this.closeModal());
        }
        
        if (this.elements.modalNextBtn) {
            this.elements.modalNextBtn.addEventListener('click', () => this.goToNextLesson());
        }
        
        if (this.elements.modal) {
            this.elements.modal.addEventListener('click', (e) => {
                if (e.target === this.elements.modal) {
                    this.closeModal();
                }
            });
        }
        
        if (this.editor && this.editor.editorElement) {
            this.editor.editorElement.addEventListener('editor:run', () => this.runCode());
        }
        
        document.addEventListener('hint:used', (e) => {
            this.updateScore(-e.detail.cost);
        });
    }

    async loadLessons() {
        try {
            const response = await fetch('/api/get-lesson.php');
            
            if (!response.ok) {
                throw new Error('Failed to load lessons');
            }
            
            const data = await response.json();
            this.lessons = data.lessons || [];
            this.renderLessonList();
            
            await this.loadProgress();
            
            if (this.lessons.length > 0) {
                this.selectLesson(this.lessons[0].id);
            }
        } catch (error) {
            console.error('Error loading lessons:', error);
            this.showOutput('Error loading lessons. Please check if the database is set up.', 'error');
        }
    }

    async loadProgress() {
        try {
            if (this.currentUser) {
                const response = await this.apiRequest('/api/progress.php');
                const data = await response.json();
                
                if (data.success && data.progress) {
                    for (const [lessonId, progress] of Object.entries(data.progress)) {
                        if (progress.score > 0) {
                            this.completedLessons.add(parseInt(lessonId));
                        }
                    }
                }
            } else {
                const anonId = this.getAnonymousUserId();
                const response = await fetch('/api/progress.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ anonymousUserId: anonId })
                });
                const data = await response.json();
                
                if (data.success && data.progress) {
                    for (const [lessonId, progress] of Object.entries(data.progress)) {
                        if (progress.score > 0) {
                            this.completedLessons.add(parseInt(lessonId));
                        }
                    }
                }
            }
            this.renderLessonList();
        } catch (error) {
            console.error('Error loading progress:', error);
        }
    }

    renderLessonList() {
        if (!this.elements.lessonList) return;
        
        this.elements.lessonList.innerHTML = '';
        
        this.lessons.forEach(lesson => {
            const li = document.createElement('li');
            li.className = 'lesson-item';
            li.dataset.lessonId = lesson.id;
            
            if (this.completedLessons.has(lesson.id)) {
                li.classList.add('completed');
            }
            
            if (this.currentLesson && this.currentLesson.id === lesson.id) {
                li.classList.add('active');
            }
            
            li.innerHTML = `
                <span>${lesson.id}. ${lesson.title}</span>
                <span class="difficulty-badge ${lesson.difficulty}">${lesson.difficulty}</span>
            `;
            
            li.addEventListener('click', () => this.selectLesson(lesson.id));
            
            this.elements.lessonList.appendChild(li);
        });
    }

    selectLesson(lessonId) {
        const lesson = this.lessons.find(l => l.id === lessonId);
        
        if (!lesson) return;
        
        this.currentLesson = lesson;
        
        if (this.elements.lessonTitle) {
            this.elements.lessonTitle.textContent = lesson.title;
        }
        
        if (this.elements.lessonDifficulty) {
            this.elements.lessonDifficulty.textContent = lesson.difficulty;
            this.elements.lessonDifficulty.className = `difficulty-badge ${lesson.difficulty}`;
        }
        
        if (this.elements.problemDescription) {
            this.elements.problemDescription.innerHTML = lesson.description;
        }
        
        if (this.editor) {
            this.editor.setCode(lesson.starterCode || '');
        }
        
        this.clearOutput();
        
        if (this.hintEngine) {
            this.hintEngine.setLessonId(lesson.id);
            this.hintEngine.setHints(lesson.hints || []);
            this.hintEngine.enable(false);
        }
        
        this.renderLessonList();
    }

    async runCode() {
        if (!this.currentLesson || !this.editor) return;
        
        const code = this.editor.getCode();
        
        this.showOutput('Running...', '');
        
        try {
            const response = await this.apiRequest('/api/validate.php', {
                method: 'POST',
                body: JSON.stringify({
                    code: code,
                    lessonId: this.currentLesson.id,
                    testCases: this.currentLesson.testCases
                })
            });
            
            const text = await response.text();
            
            if (!text || text.trim() === '') {
                this.showOutput('Error: Empty response from server', 'error');
                return;
            }
            
            let result;
            try {
                result = JSON.parse(text);
            } catch (e) {
                this.showOutput('Error: Invalid JSON response\n' + text.substring(0, 200), 'error');
                return;
            }
            
            if (result.success) {
                this.showOutput('✓ All tests passed!', 'success');
                this.handleLessonComplete(result.score || this.currentLesson.points);
            } else if (result.fallback) {
                this.runClientSide(code);
            } else {
                let output = `✗ ${result.message}\n\n`;
                
                if (result.results && result.results.length > 0) {
                    output += 'Test Results:\n';
                    result.results.forEach((r, i) => {
                        const status = r.passed ? '✓' : '✗';
                        const inputStr = JSON.stringify(r.input);
                        output += `\n${status} Test ${i + 1}: Input(${inputStr})\n   Expected: ${r.expected}\n   Actual: ${r.actual || 'N/A'}`;
                        if (r.error) {
                            output += `\n   Error: ${r.error}`;
                        }
                    });
                }
                
                this.showOutput(output, 'error');
                
                if (this.hintEngine && this.currentLesson.hints) {
                    this.hintEngine.enable(true);
                }
            }
        } catch (error) {
            console.error('Validation error:', error);
            this.showOutput('Error: ' + error.message, 'error');
        }
    }

    runClientSide(code) {
        try {
            const testCases = this.currentLesson.testCases;
            let allPassed = true;
            let results = [];
            
            for (const testCase of testCases) {
                try {
                    const func = new Function(code + `\nreturn ${this.currentLesson.solution.split('(')[0].replace('function ', '')};`);
                    
                    const funcName = this.currentLesson.solution.match(/function\s+(\w+)/)[1];
                    const userFunc = new Function(code + `\nreturn ${funcName};`);
                    
                    let result;
                    if (testCase.input && testCase.input.length > 0) {
                        result = userFunc()(testCase.input[0], testCase.input[1]);
                    } else {
                        result = userFunc()();
                    }
                    
                    const passed = result == testCase.expected;
                    allPassed = allPassed && passed;
                    
                    results.push({
                        input: testCase.input,
                        expected: testCase.expected,
                        actual: result,
                        passed: passed
                    });
                } catch (e) {
                    results.push({
                        input: testCase.input,
                        expected: testCase.expected,
                        error: e.message,
                        passed: false
                    });
                    allPassed = false;
                }
            }
            
            if (allPassed) {
                this.showOutput('✓ All tests passed!', 'success');
                this.handleLessonComplete(this.currentLesson.points);
            } else {
                const failedTests = results.filter(r => !r.passed);
                this.showOutput(`✗ ${failedTests.length} test(s) failed`, 'error');
                
                if (this.hintEngine && this.currentLesson.hints) {
                    this.hintEngine.enable(true);
                }
            }
        } catch (error) {
            this.showOutput(`Error: ${error.message}`, 'error');
        }
    }

    handleLessonComplete(score) {
        if (!this.currentLesson) return;
        
        this.completedLessons.add(this.currentLesson.id);
        
        this.updateScore(score);
        
        this.showModal(
            'Lesson Complete!',
            `You completed "${this.currentLesson.title}"`,
            score
        );
        
        this.submitScore(this.currentLesson.id, score);
        
        this.renderLessonList();
    }

    async submitScore(lessonId, score) {
        try {
            const userInfo = this.getProgressUserId();
            
            await this.apiRequest('/api/submit-score.php', {
                method: 'POST',
                body: JSON.stringify({
                    lessonId: lessonId,
                    score: score,
                    hintsUsed: this.hintEngine ? this.hintEngine.getHintsUsed() : 0,
                    anonymousUserId: userInfo.isAuthenticated ? null : userInfo.id
                })
            });
        } catch (error) {
            console.error('Error submitting score:', error);
        }
    }

    updateScore(points) {
        this.score += points;
        
        if (this.elements.scoreDisplay) {
            const valueElement = this.elements.scoreDisplay.querySelector('.score-value');
            if (valueElement) {
                valueElement.textContent = this.score;
            }
        }
    }

    resetCode() {
        if (!this.currentLesson || !this.editor) return;
        
        this.editor.setCode(this.currentLesson.starterCode || '');
        this.clearOutput();
    }

    clearOutput() {
        if (this.elements.output) {
            this.elements.output.textContent = '';
            this.elements.output.className = 'output';
        }
    }

    showOutput(message, type) {
        if (this.elements.output) {
            this.elements.output.textContent = message;
            this.elements.output.className = `output ${type}`;
        }
    }

    showModal(title, message, score) {
        if (!this.elements.modal) return;
        
        if (this.elements.modalTitle) {
            this.elements.modalTitle.textContent = title;
        }
        
        if (this.elements.modalMessage) {
            this.elements.modalMessage.textContent = message;
        }
        
        if (this.elements.modalScore) {
            this.elements.modalScore.textContent = `+${score} points`;
        }
        
        this.elements.modal.classList.add('show');
    }

    closeModal() {
        if (this.elements.modal) {
            this.elements.modal.classList.remove('show');
        }
    }

    goToNextLesson() {
        this.closeModal();
        
        const currentIndex = this.lessons.findIndex(l => l.id === this.currentLesson?.id);
        
        if (currentIndex >= 0 && currentIndex < this.lessons.length - 1) {
            this.selectLesson(this.lessons[currentIndex + 1].id);
        }
    }
}

// Initialize the application
const app = new LogicIDE();
