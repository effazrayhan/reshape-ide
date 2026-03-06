/**
 * Project: Logic-Focused Educational IDE
 * File: public/js/editor.js
 * Description: Code editor functionality for the Logic IDE
 */

class CodeEditor {
    constructor(elementId) {
        this.editorElement = document.getElementById(elementId);
        
        if (!this.editorElement) {
            console.error('Editor element not found');
            return;
        }

        this.initEditor();
    }

    initEditor() {
        // Handle tab key for indentation
        this.editorElement.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                e.preventDefault();
                this.insertText('    ');
            }
            
            // Ctrl/Cmd + Enter to run code
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                const runEvent = new CustomEvent('editor:run');
                this.editorElement.dispatchEvent(runEvent);
            }
            
            // Ctrl/Cmd + S to save (prevent default save dialog)
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
            }
        });
    }

    insertText(text) {
        const start = this.editorElement.selectionStart;
        const end = this.editorElement.selectionEnd;
        const value = this.editorElement.value;
        
        this.editorElement.value = value.substring(0, start) + text + value.substring(end);
        
        // Move cursor after inserted text
        this.editorElement.selectionStart = this.editorElement.selectionEnd = start + text.length;
        
        this.editorElement.focus();
    }

    getCode() {
        return this.editorElement.value;
    }

    setCode(code) {
        this.editorElement.value = code;
    }

    clear() {
        this.editorElement.value = '';
    }

    reset() {
        this.clear();
        this.setCode('// Write your code here\n');
    }

    focus() {
        this.editorElement.focus();
    }

    getCursorPosition() {
        return {
            line: this.editorElement.value.substring(0, this.editorElement.selectionStart).split('\n').length,
            column: this.editorElement.selectionStart - this.editorElement.value.lastIndexOf('\n', this.editorElement.selectionStart - 1) - 1
        };
    }
}

// Export for use in other modules
window.CodeEditor = CodeEditor;
