document.addEventListener("DOMContentLoaded", () => {
    setupMobileMenu();
    handleTabSwitching();
    setupFormHandlers();
});

// Function to toggle mobile menu
function setupMobileMenu() {
    const menuBtn = document.querySelector(".menu-btn i");
    const navigation = document.querySelector(".navigation");
    
    if (menuBtn && navigation) {
        menuBtn.addEventListener("click", () => {
            menuBtn.classList.toggle("fa-bars");
            menuBtn.classList.toggle("fa-times");
            navigation.classList.toggle("active");
        });
    }
}

// Function to switch between login and signup tabs
function handleTabSwitching() {
    const loginTab = document.getElementById("login-tab");
    const signupTab = document.getElementById("signup-tab");
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.get("tab") === "signup") {
        switchTab("signup");
    }
    
    if (loginTab && signupTab) {
        loginTab.addEventListener("click", () => switchTab("login"));
        signupTab.addEventListener("click", () => switchTab("signup"));
    }
}

function switchTab(tab) {
    const loginTab = document.getElementById("login-tab");
    const signupTab = document.getElementById("signup-tab");
    const loginContent = document.getElementById("login-content");
    const signupContent = document.getElementById("signup-content");
    const cardDescription = document.getElementById("card-description");
    
    if (!loginTab || !signupTab || !loginContent || !signupContent || !cardDescription) return;
    
    if (tab === "signup") {
        signupTab.classList.add("active");
        loginTab.classList.remove("active");
        signupContent.classList.add("active");
        loginContent.classList.remove("active");
        cardDescription.innerText = "Create your account to start exploring";
    } else {
        loginTab.classList.add("active");
        signupTab.classList.remove("active");
        loginContent.classList.add("active");
        signupContent.classList.remove("active");
        cardDescription.innerText = "Sign in to continue your journey";
    }
}

// Function to validate role selection
function validateRoleSelection(roleElement) {
    if (!roleElement || !roleElement.value) {
        alert("Please select your role.");
        return false;
    }
    return true;
}

// Function to handle login and signup events
function handleAuth(event, type) {
    event.preventDefault();
    const button = document.getElementById(`${type}-button`);
    const role = document.getElementById(`${type}-role`);
    
    if (!validateRoleSelection(role)) return;

    button.disabled = true;
    button.textContent = type === "login" ? "Logging in..." : "Creating account...";
    
    setTimeout(() => {
        button.disabled = false;
        button.textContent = type === "login" ? "Login" : "Create Account";
        alert(`${type.charAt(0).toUpperCase() + type.slice(1)} Successful as ${role.value.charAt(0).toUpperCase() + role.value.slice(1)}`);
        window.location.href = "/";
    }, 1500);
}

// Function to set up form event listeners
function setupFormHandlers() {
    document.getElementById("login-form")?.addEventListener("submit", (event) => handleAuth(event, "login"));
    document.getElementById("signup-form")?.addEventListener("submit", (event) => handleAuth(event, "signup"));
}

// Function to redirect back to homepage
function goBack() {
    window.location.href = "index.html";
}
