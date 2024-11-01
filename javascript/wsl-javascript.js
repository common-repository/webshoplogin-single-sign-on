function showError(errorMessage) {
    var span = document.createElement('span');
    span.style.color = "red";
    span.appendChild(document.createTextNode(errorMessage));

    var wslparentdiv = document.getElementsByClassName("webshoploginbuttonparent");
    wslparentdiv[0].prepend(span);

    var timer = errorMessage.length * 50 + 2000;
    setTimeout(function () {
        span.parentNode.removeChild(span);
    }, timer);

    return false;
}

function wslLoginUser(event) {
    var accesstoken = event.detail.accesstoken;
    var authorized = event.detail.authorized;

    if(!authorized || !accesstoken) {
        return false;
    }

    var xhr = new XMLHttpRequest();
    xhr.open("POST", get_site_url + "/wsl_login_api", true)
    xhr.onreadystatechange = function () {
        if(this.readyState === 4) {
            wslLoginXHRCallback(this);
        }
    }
    xhr.send(JSON.stringify(event.detail));

}

function wslLoginXHRCallback(event) {
    //Called when a login call to /wsl_api is finished

    if(event.status !== 200) {
        showError(error_message);
        return false;
    }

    var responseText = event.responseText;

    switch (responseText) {
        case "OK" :
            location.reload();
            break;
        case "ERROR" :
            showError(error_message);
            break;
        case "ERROR-NOT-VERIFIED" :
            showError(error_not_verified);
            break;
        case "ERROR-NO-ACCOUNT-DATA" :
            showError(error_no_account_data);
            break;
        default :
            showError(error_message);
            break;

    }
}

function wslSyncUser(event) {
    var accesstoken = event.detail.accesstoken;
    var authorized = event.detail.authorized;

    if(!authorized || !accesstoken) {
        return false;
    }

    if(is_checkout) {
        webshoplogin.loader();
    }

    var xhr = new XMLHttpRequest();
    xhr.open("POST", get_site_url + "/wsl_sync_api", true)
    xhr.onreadystatechange = function () {
        if(this.readyState === 4) {
            wslSyncXHRCallback(this);
        }
    }
    xhr.send(JSON.stringify(event.detail));
}

function wslSyncXHRCallback(event) {
    //Called when a sync call to /wsl_api is finished
    if(event.status !== 200) {
        return false;
    }

    if(event.responseText !== "OK") {
        return false;
    }

    if(is_checkout){
        location.reload();
    }

    return true;
}

function wslButtonClickCallback(event) {
    wslLoginUser(event);
}

document.addEventListener("webshoploginLoaded", function(event) {
    if(!is_user_logged_in) {
        if (webshoplogin.user.loggedIn && webshoplogin.user.autologin) {
            document.addEventListener("getAccesstokenResponse", wslLoginUser);
            webshoplogin.accesstoken();
        }
    } else {
        if (!webshoplogin.user.isSync) {
            document.addEventListener("getAccesstokenResponse", wslSyncUser);
            webshoplogin.accesstoken();
        }
    }

    document.getElementById('wsl-button').addEventListener('buttonresponse', wslButtonClickCallback);
});