let fontSize = Number.parseInt(localStorage.getItem('timetableFontSize') ?? '100', 10);
if (Number.isNaN(fontSize)) {
    fontSize = 100;
}

let showTeacherNames = localStorage.getItem('showTeacherNames') !== 'false';
let showClassInfo = localStorage.getItem('showClassInfo') !== 'false';

function updateFontSize() {
    document.querySelectorAll('.timetable-container').forEach((element) => {
        element.style.fontSize = `${fontSize}%`;
    });

    document.querySelectorAll('.font-size-display').forEach((display) => {
        display.textContent = `${fontSize}%`;
    });
}

function updateTeacherDisplay() {
    document.querySelectorAll('.teacher-name').forEach((element) => {
        element.style.display = showTeacherNames ? 'block' : 'none';
    });

    const button = document.getElementById('toggle-teacher-btn');
    if (!button) {
        return;
    }

    button.classList.toggle('opacity-60', !showTeacherNames);
    button.classList.toggle('bg-white/10', showTeacherNames);
    button.classList.toggle('bg-white/5', !showTeacherNames);
}

function updateClassDisplay() {
    document.querySelectorAll('.class-info').forEach((element) => {
        element.style.display = showClassInfo ? 'block' : 'none';
    });

    const button = document.getElementById('toggle-class-btn');
    if (!button) {
        return;
    }

    button.classList.toggle('bg-blue-600', showClassInfo);
    button.classList.toggle('text-white', showClassInfo);
    button.classList.toggle('bg-gray-200', !showClassInfo);
    button.classList.toggle('dark:bg-gray-700', !showClassInfo);
    button.classList.toggle('text-gray-700', !showClassInfo);
    button.classList.toggle('dark:text-gray-300', !showClassInfo);
}

window.switchClass = function switchClass(classId) {
    const cards = document.querySelectorAll('[data-class-card]');
    cards.forEach((card) => {
        if (card.dataset.classCard === classId) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });

    const buttons = document.querySelectorAll('[data-class-button]');
    buttons.forEach((button) => {
        if (button.dataset.classButton === classId) {
            button.classList.add('bg-blue-600', 'text-white');
            button.classList.remove(
                'bg-white',
                'text-gray-700',
                'hover:bg-gray-50',
                'dark:bg-gray-800',
                'dark:text-gray-300',
                'dark:hover:bg-gray-700',
            );
        } else {
            button.classList.remove('bg-blue-600', 'text-white');
            button.classList.add(
                'bg-white',
                'text-gray-700',
                'hover:bg-gray-50',
                'dark:bg-gray-800',
                'dark:text-gray-300',
                'dark:hover:bg-gray-700',
            );
        }
    });
};

window.switchTerm = function switchTerm() {
    const selector = document.getElementById('term-selector');
    const termId = selector?.value;

    if (!termId) {
        return;
    }

    window.location.href = `?term=${termId}`;
};

window.toggleTheme = function toggleTheme() {
    if (document.documentElement.classList.contains('dark')) {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');

        return;
    }

    document.documentElement.classList.add('dark');
    localStorage.setItem('theme', 'dark');
};

window.increaseFontSize = function increaseFontSize() {
    fontSize = Math.min(150, fontSize + 10);
    localStorage.setItem('timetableFontSize', String(fontSize));
    updateFontSize();
};

window.decreaseFontSize = function decreaseFontSize() {
    fontSize = Math.max(70, fontSize - 10);
    localStorage.setItem('timetableFontSize', String(fontSize));
    updateFontSize();
};

window.toggleTeacherNames = function toggleTeacherNames() {
    showTeacherNames = !showTeacherNames;
    localStorage.setItem('showTeacherNames', showTeacherNames ? 'true' : 'false');
    updateTeacherDisplay();
};

window.toggleClassInfo = function toggleClassInfo() {
    showClassInfo = !showClassInfo;
    localStorage.setItem('showClassInfo', showClassInfo ? 'true' : 'false');
    updateClassDisplay();
};

document.addEventListener('DOMContentLoaded', () => {
    updateFontSize();
    updateTeacherDisplay();
    updateClassDisplay();
});
