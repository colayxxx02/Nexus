(function () {
    const dateInput = document.getElementById('date');
    const topbar = document.querySelector('.topbar');
    if (!dateInput || !topbar) return;

    const controls = document.createElement('div');
    controls.className = 'topbar-actions';
    controls.innerHTML = '<button id="enable-alarms" class="btn btn-light" type="button">Enable alarms</button><span id="alarm-status" class="alarm-status">Alarms are off</span>';
    topbar.appendChild(controls);

    const toast = document.createElement('div');
    toast.id = 'alarm-toast';
    toast.className = 'alarm-toast';
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    document.body.appendChild(toast);

    const enableButton = document.getElementById('enable-alarms');
    const alarmStatus = document.getElementById('alarm-status');
    const alertedTasks = new Set();
    let tasks = [];
    let audioContext = null;
    let alarmsEnabled = false;

    function showToast(message) {
        toast.textContent = message;
        toast.classList.add('visible');
        window.setTimeout(() => toast.classList.remove('visible'), 8000);
    }

    function playAlarm() {
        if (!audioContext) return;
        const now = audioContext.currentTime;
        [0, 0.24, 0.48].forEach((offset, index) => {
            const oscillator = audioContext.createOscillator();
            const gain = audioContext.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.value = index % 2 === 0 ? 880 : 660;
            gain.gain.setValueAtTime(0.0001, now + offset);
            gain.gain.exponentialRampToValueAtTime(0.22, now + offset + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + offset + 0.18);
            oscillator.connect(gain);
            gain.connect(audioContext.destination);
            oscillator.start(now + offset);
            oscillator.stop(now + offset + 0.2);
        });
    }

    async function loadTasks() {
        try {
            const response = await fetch('api/tasks.php?date=' + encodeURIComponent(dateInput.value), { cache: 'no-store' });
            const payload = await response.json();
            tasks = Array.isArray(payload.tasks) ? payload.tasks : [];
        } catch (error) {
            tasks = [];
        }
    }

    function checkAlarms() {
        if (!alarmsEnabled) return;
        const now = new Date();
        const date = [now.getFullYear(), String(now.getMonth() + 1).padStart(2, '0'), String(now.getDate()).padStart(2, '0')].join('-');
        const time = [String(now.getHours()).padStart(2, '0'), String(now.getMinutes()).padStart(2, '0')].join(':');
        tasks.forEach((task) => {
            if (task.status === 'done' || !task.start_time || task.task_date !== date || task.start_time.slice(0, 5) !== time) return;
            const key = String(task.id) + ':' + task.task_date + ':' + task.start_time;
            if (alertedTasks.has(key)) return;
            alertedTasks.add(key);
            playAlarm();
            if (navigator.vibrate) navigator.vibrate([220, 100, 220]);
            showToast('⏰ ' + task.title + ' is scheduled now.');
            if ('Notification' in window && Notification.permission === 'granted') new Notification('NEXUS reminder', { body: task.title });
        });
    }

    async function enableAlarms() {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (AudioContextClass) {
            audioContext = audioContext || new AudioContextClass();
            await audioContext.resume();
        }
        if ('Notification' in window && Notification.permission === 'default') await Notification.requestPermission();
        alarmsEnabled = true;
        alarmStatus.textContent = 'Alarms enabled';
        alarmStatus.classList.add('active');
        enableButton.textContent = 'Alarms enabled';
        showToast('Alarms are ready while this planner page stays open.');
        await loadTasks();
        checkAlarms();
    }

    enableButton.addEventListener('click', enableAlarms);
    loadTasks();
    window.setInterval(loadTasks, 60000);
    window.setInterval(checkAlarms, 15000);
})();
