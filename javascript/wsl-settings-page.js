function clickSettingsTab() {
    var hidediv = document.getElementById('wsl-developers');
    hidediv.style.display = 'none';

    hidediv = document.getElementById('wsl-buttons');
    hidediv.style.display = 'none';

    var div = document.getElementById('wsl-settings');
    div.style.display = 'block';

    var hidetab = document.getElementById('wsl-developers-tab');
    hidetab.className = hidetab.className.replace('nav-tab-active', '');

    hidetab = document.getElementById('wsl-buttons-tab');
    hidetab.className = hidetab.className.replace('nav-tab-active', '');

    var tab = document.getElementById('wsl-settings-tab');
    tab.classList.add('nav-tab-active');
}

function clickWslTab() {
    var hidediv = document.getElementById('wsl-settings');
    hidediv.style.display = 'none';

    hidediv = document.getElementById('wsl-buttons');
    hidediv.style.display = 'none';

    var div = document.getElementById('wsl-developers');
    div.style.display = 'block';

    var hidetab = document.getElementById('wsl-settings-tab');
    hidetab.className = hidetab.className.replace('nav-tab-active', '');

    hidetab = document.getElementById('wsl-buttons-tab');
    hidetab.className = hidetab.className.replace('nav-tab-active', '');

    var tab = document.getElementById('wsl-developers-tab');
    tab.classList.add('nav-tab-active');
}

function clickButtonsTab() {
    var hidediv = document.getElementById('wsl-settings');
    hidediv.style.display = 'none';

    hidediv = document.getElementById('wsl-developers');
    hidediv.style.display = 'none';

    var div = document.getElementById('wsl-buttons');
    div.style.display = 'block';

    var hidetab = document.getElementById('wsl-settings-tab');
    hidetab.className = hidetab.className.replace('nav-tab-active', '');

    hidetab = document.getElementById('wsl-developers-tab');
    hidetab.className = hidetab.className.replace('nav-tab-active', '');

    var tab = document.getElementById('wsl-buttons-tab');
    tab.classList.add('nav-tab-active');
}

window.addEventListener("load", function() {
    var collContent = document.getElementById("collapsible-content");
    var collButton = document.getElementById("collapsible-button");
    collButton.addEventListener("click", function () {
        this.classList.toggle("collapsible-button-active");
        if (collContent.style.display === "block") {
            collContent.style.display = "none";
        } else {
            collContent.style.display = "block";
        }
    });
});

