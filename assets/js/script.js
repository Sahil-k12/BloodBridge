/**
 * BloodBridge - JavaScript Functionality
 * Blood Donation & Emergency Assistance System
 */

// Counter Animation for Statistics
document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('.counter');
    const speed = 100;
    
    const runCounter = (counter) => {
        const target = +counter.getAttribute('data-target');
        const increment = target / speed;
        
        const updateCount = () => {
            const count = +counter.innerText;
            
            if (count < target) {
                counter.innerText = Math.ceil(count + increment);
                setTimeout(updateCount, 10);
            } else {\n                counter.innerText = target.toLocaleString();\n            }\n        };\n        \n        updateCount();\n    };\n    \n    const observerOptions = {\n        threshold: 0.5,\n        rootMargin: '0px 0px -100px 0px'\n    };\n    \n    const observer = new IntersectionObserver((entries) => {\n        entries.forEach(entry => {\n            if (entry.isIntersecting && entry.target.classList.contains('counter')) {\n                runCounter(entry.target);\n                observer.unobserve(entry.target);\n            }\n        });\n    }, observerOptions);\n    \n    counters.forEach(counter => observer.observe(counter));\n});\n\n// Toast Notification\nfunction showToast(message, type = 'success') {\n    const toast = document.createElement('div');\n    const typeClass = `alert-${type}`;\n    toast.className = `alert ${typeClass} alert-dismissible fade show`;\n    toast.innerHTML = `\n        ${message}\n        <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>\n    `;\n    toast.style.position = 'fixed';\n    toast.style.top = '20px';\n    toast.style.right = '20px';\n    toast.style.zIndex = '9999';\n    toast.style.minWidth = '300px';\n    document.body.appendChild(toast);\n    \n    setTimeout(() => {\n        toast.remove();\n    }, 5000);\n}\n\n// Form Validation\nfunction validateEmail(email) {\n    const re = /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/;\n    return re.test(email);\n}\n\nfunction validatePhone(phone) {\n    const re = /^[0-9\\-\\+\\(\\) ]{7,20}$/;\n    return re.test(phone);\n}\n\nfunction validatePassword(password) {\n    return password.length >= 6;\n}\n\n// Smooth scroll for anchor links\ndocument.querySelectorAll('a[href^=\"#\"]').forEach(anchor => {\n    anchor.addEventListener('click', function (e) {\n        e.preventDefault();\n        const target = document.querySelector(this.getAttribute('href'));\n        if (target) {\n            target.scrollIntoView({\n                behavior: 'smooth',\n                block: 'start'\n            });\n        }\n    });\n});\n\n// Dropdown menu functionality\ndocument.querySelectorAll('.dropdown-toggle').forEach(toggle => {\n    toggle.addEventListener('click', function() {\n        this.classList.toggle('active');\n    });\n});\n\n// Modal functionality\nfunction openModal(modalId) {\n    const modal = document.getElementById(modalId);\n    if (modal) {\n        modal.style.display = 'block';\n    }\n}\n\nfunction closeModal(modalId) {\n    const modal = document.getElementById(modalId);\n    if (modal) {\n        modal.style.display = 'none';\n    }\n}\n\n// Close modal when clicking outside\nwindow.addEventListener('click', function(event) {\n    if (event.target.classList.contains('modal')) {\n        event.target.style.display = 'none';\n    }\n});\n