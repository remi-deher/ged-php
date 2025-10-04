// public/js/utils.js

GED.utils = {
    showToast(message, icon = 'ℹ️') {
        const container = document.getElementById("toast-container");
        if (!container) return;
        const toast = document.createElement("div");
        toast.className = "toast";
        toast.innerHTML = `<span style="margin-right: 8px;">${icon}</span> ${message}`;
        container.appendChild(toast);
        setTimeout(() => toast.classList.add("show"), 100);
        setTimeout(() => {
            toast.classList.remove("show");
            toast.addEventListener("transitionend", () => toast.remove());
        }, 5000);
    },

    createAndSubmitForm(action, method, data) {
        const form = document.createElement('form');
        form.action = action;
        form.method = method;

        for (const key in data) {
            if (Array.isArray(data[key])) {
                data[key].forEach(value => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `${key}[]`;
                    input.value = value;
                    form.appendChild(input);
                });
            } else {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = data[key];
                form.appendChild(input);
            }
        }

        document.body.appendChild(form);
        form.submit();
    }
};
