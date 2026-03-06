/**
 * Project: Logic-Focused Educational IDE
 * File: public/js/app.js
 * Description: Main application logic for the Logic IDE
 */

class LogicIDE {
    constructor() {
        this.currentLesson = null;
        this.score = 0;
        this.lessons = [];
        this.completedLessons = new Set();
        
        // Initialize components
        this.editor = null;
        this.hintEngine = null;
        
        // DOM elements
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
            modalClose: document.querySelector('.modal-close')
        };
        
        this.init();
    }

    async init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupApp());
        } else {
            this.setupApp();
        }
    }

    setupApp() {
        // Initialize editor
        this.editor = new CodeEditor('code-editor');
        
        // Initialize hint engine
        this.hintEngine = new HintEngine();
        
        // Set up event listeners
        this.setupEventListeners();
        
        // Load initial data
        this.loadLessons();
    }

    setupEventListeners() {
        // Run button
        if (this.elements.runBtn) {
            this.elements.runBtn.addEventListener('click', () => this.runCode());
        }
        
        // Reset button
        if (this.elements.resetBtn) {
            this.elements.resetBtn.addEventListener('click', () => this.resetCode());
        }
        
        // Clear output button
        if (this.elements.clearOutputBtn) {
            this.elements.clearOutputBtn.addEventListener('click', () => this.clearOutput());
        }
        
        // Modal close
        if (this.elements.modalClose) {
            this.elements.modalClose.addEventListener('click', () => this.closeModal());
        }
        
        // Modal next button
        if (this.elements.modalNextBtn) {
            this.elements.modalNextBtn.addEventListener('click', () => this.goToNextLesson());
        }
        
        // Close modal on outside click
        if (this.elements.modal) {
            this.elements.modal.addEventListener('click', (e) => {
                if (e.target === this.elements.modal) {
                    this.closeModal();
                }
            });
        }
        
        // Editor run event
        if (this.editor && this.editor.editorElement) {
            this.editor.editorElement.addEventListener('editor:run', () => this.runCode());
        }
        
        // Hint used event
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
            
            // Select first lesson if available
            if (this.lessons.length > 0) {
                this.selectLesson(this.lessons[0].id);
            }
        } catch (error) {
            console.error('Error loading lessons:', error);
            this.showOutput('Error loading lessons. Please try again.', 'error');
            
            // Load demo lessons for offline testing
            this.loadDemoLessons();
        }
    }

    loadDemoLessons() {
        this.lessons = [
            {
                id: 1,
                title: 'Hello World',
                difficulty: 'easy',
                description: '<h3>Your First Program</h3><p>Write a function that returns the string "Hello, World!"</p><pre><code>function hello() {\n    // Your code here\n}</code></pre>',
                starterCode: 'function hello() {\n    // Return "Hello, World!"\n    \n}',
                solution: 'function hello() {\n    return "Hello, World!";\n}',
                hints: [
                    { id: 1, text: 'Use the return keyword to return a value from a function.' },
                    { id: 2, text: 'Strings in JavaScript are wrapped in quotes.' },
                    { id: 3, text: 'The answer is: return "Hello, World!";' }
                ],
                testCases: [
                    { input: '', expected: 'Hello, World!' }
                ],
                points: 100
            },
            {
                id: 2,
                title: 'Sum Two Numbers',
                difficulty: 'easy',
                description: '<h3>Adding Numbers</h3><p>Write a function that takes two numbers and returns their sum.</p><pre><code>function sum(a, b) {\n    // Your code here\n}</code></pre>',
                starterCode: 'function sum(a, b) {\n    // Return a + b\n    \n}',
                solution: 'function sum(a, b) {\n    return a + b;\n}',
                hints: [
                    { id: 1, text: 'You can add two numbers using the + operator.' },
                    { id: 2, text: 'Make sure to use the return keyword.' },
                    { id: 3, text: 'The answer is: return a + b;' }
                ],
                testCases: [
                    { input: [2, 3], expected: 5 },
                    { input: [10, 20], expected: 30 },
                    { input: [-1, 1], expected: 0 }
                ],
                points: 100
            },
            {
                id: 3,
                title: 'Reverse a String',
                difficulty: 'medium',
                description: '<h3>String Reversal</h3><p>Write a function that reverses a string.</p><pre><code>function reverse(str) {\n    // Your code here\n}</code></pre>',
                starterCode: 'function reverse(str) {\n    // Return the reversed string\n    \n}',
                solution: 'function reverse(str) {\n    return str.split("").reverse().join("");\n}',
                hints: [
                    { id: 1, text: 'You can convert a string to an array using split().' },
                    { id: 2, text: 'Arrays have a reverse() method.' },
                    { id: 3, text: 'Use join() to convert the array back to a string.' }
                ],
                testCases: [
                    { input: ['hello'], expected: 'olleh' },
                    { input: ['JavaScript'], expected: 'tpircSavaJ' },
                    { input: ['a'], expected: 'a' }
                ],
                points: 150
            },
            {
                id: 4,
                title: 'FizzBuzz',
                difficulty: 'medium',
                description: '<h3>The Classic Problem</h3><p>Write a function that takes a number and returns:</p><ul><li>"Fizz" if divisible by 3</li><li>"Buzz" if divisible by 5</li><li>"FizzBuzz" if divisible by both</li><li>The number as a string otherwise</li></ul><pre><code>function fizzBuzz(n) {\n    // Your code here\n}</code></pre>',
                starterCode: 'function fizzBuzz(n) {\n    // Your code here\n    \n}',
                solution: 'function fizzBuzz(n) {\n    if (n % 3 === 0 && n % 5 === 0) return "FizzBuzz";\n    if (n % 3 === 0) return "Fizz";\n    if (n % 5 === 0) return "Buzz";\n    return String(n);\n}',
                hints: [
                    { id: 1, text: 'Use the modulo operator (%) to check divisibility.' },
                    { id: 2, text: 'Check for divisibility by both 3 and 5 first.' },
                    { id: 3, text: 'Use String(n) to convert a number to a string.' }
                ],
                testCases: [
                    { input: [3], expected: 'Fizz' },
                    { input: [5], expected: 'Buzz' },
                    { input: [15], expected: 'FizzBuzz' },
                    { input: [7], expected: '7' }
                ],
                points: 200
            },
            {
                id: 5,
                title: 'Factorial',
                difficulty: 'hard',
                description: '<h3>Factorial Calculation</h3><p>Write a function that calculates the factorial of a non-negative integer.</p><pre><code>function factorial(n) {\n    // Your code here\n}</code></pre>',
                starterCode: 'function factorial(n) {\n    // Return n! (n factorial)\n    \n}',
                solution: 'function factorial(n) {\n    if (n <= 1) return 1;\n    return n * factorial(n - 1);\n}',
                hints: [
                    { id: 1, text: 'Factorial of 0 or 1 is 1.' },
                    { id: 2, text: 'You can use recursion: n! = n * (n-1)!' },
                    { id: 3, text: 'Base case: if (n <= 1) return 1;' }
                ],
                testCases: [
                    { input: [0], expected: 1 },
                    { input: [1], expected: 1 },
                    { input: [5], expected: 120 },
                    { input: [10], expected: 3628800 }
                ],
                points: 250
            }
        ];
        
        this.renderLessonList();
        
        if (this.lessons.length > 0) {
            this.selectLesson(this.lessons[0].id);
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
        
        // Update UI
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
        
        // Reset editor
        if (this.editor) {
            this.editor.setCode(lesson.starterCode || '');
        }
        
        // Clear output
        this.clearOutput();
        
        // Reset hints
        if (this.hintEngine) {
            this.hintEngine.setHints(lesson.hints || []);
            this.hintEngine.enable(false);
        }
        
        // Update lesson list UI
        this.renderLessonList();
    }

    async runCode() {
        if (!this.currentLesson || !this.editor) return;
        
        const code = this.editor.getCode();
        
        this.showOutput('Running...', '');
        
        try {
            // Validate code against server
            const response = await fetch('/api/validate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    code: code,
                    lessonId: this.currentLesson.id,
                    testCases: this.currentLesson.testCases
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showOutput('✓ All tests passed!', 'success');
                this.handleLessonComplete(result.score || this.currentLesson.points);
            } else {
                this.showOutput(`✗ ${result.message}`, 'error');
                
                // Enable hints after first failed attempt
                if (this.hintEngine && this.currentLesson.hints) {
                    this.hintEngine.enable(true);
                }
            }
        } catch (error) {
            console.error('Validation error:', error);
            
            // Fallback to client-side evaluation for demo
            this.runClientSide(code);
        }
    }

    runClientSide(code) {
        try {
            // Create a sandboxed evaluation
            const testCases = this.currentLesson.testCases;
            let allPassed = true;
            let results = [];
            
            for (const testCase of testCases) {
                try {
                    // Create function from code
                    const func = new Function(code + `\nreturn ${this.currentLesson.solution.split('(')[0].replace('function ', '')};`);
                    
                    // Get function name from solution
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
                
                // Enable hints after failed attempt
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
        
        // Mark lesson as complete
        this.completedLessons.add(this.currentLesson.id);
        
        // Update score
        this.updateScore(score);
        
        // Show success modal
        this.showModal(
            'Lesson Complete!',
            `You completed "${this.currentLesson.title}"`,
            score
        );
        
        // Submit score to server
        this.submitScore(this.currentLesson.id, score);
        
        // Update lesson list
        this.renderLessonList();
    }

    async submitScore(lessonId, score) {
        try {
            await fetch('/api/submit-score.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    lessonId: lessonId,
                    score: score,
                    hintsUsed: this.hintEngine ? this.hintEngine.getHintsUsed() : 0
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
