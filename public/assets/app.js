(function () {
    const tasks = Array.isArray(window.NEXUS_TASKS) ? window.NEXUS_TASKS : [];
    const enableButton = document.getElementById('enable-alarms');
    const alarmStatus = document.getElementById('alarm-status');
    const toast = document.getElementById('alarm-toast');
    const alertedTasks = new Set();
    let audioContext = null;
    let alarmsEnabled = false;

    function setStatus(message, active) {
        if (!alarmStatus) return;
        alarmStatus.textContent = message;
        alarmStatus.classList.toggle('active', active);
    }

    function showToast(message) {
        if (!toast) return;
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

    function currentDateAndTime() {
        const now = new Date();
        const date = [now.getFullYear(), String(now.getMonth() + 1).padStart(2, '0'), String(now.getDate()).padStart(2, '0')].join('-');
        const time = [String(now.getHours()).padStart(2, '0'), String(now.getMinutes()).padStart(2, '0')].join(':');
        return { date, time };
    }

    function checkAlarms() {
        if (!alarmsEnabled) return;
        const current = currentDateAndTime();
        tasks.forEach((task) => {
            if (task.status === 'done' || !task.start_time || task.task_date !== current.date || task.start_time !== current.time) return;
            const key = String(task.id) + ':' + task.task_date + ':' + task.start_time;
            if (alertedTasks.has(key)) return;
            alertedTasks.add(key);
            playAlarm();
            if (navigator.vibrate) navigator.vibrate([220, 100, 220]);
            showToast('⏰ ' + task.title + ' is scheduled now.');
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('NEXUS reminder', { body: task.title });
            }
        });
    }

    async function enableAlarms() {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (AudioContextClass) {
            audioContext = audioContext || new AudioContextClass();
            await audioContext.resume();
        }
        if ('Notification' in window && Notification.permission === 'default') {
            await Notification.requestPermission();
        }
        alarmsEnabled = true;
        setStatus('Alarms enabled', true);
        if (enableButton) enableButton.textContent = 'Alarms enabled';
        showToast('Alarms are ready while this planner page stays open.');
        checkAlarms();
    }

    if (enableButton) enableButton.addEventListener('click', enableAlarms);
    window.setInterval(checkAlarms, 15000);
})();
