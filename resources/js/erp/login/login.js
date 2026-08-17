import { loginUser } from './authentication.js';
import { sendForgotPassword } from './forgot_password.js';

$(document).on('click', '#login-btn', loginUser);
$(document).on('click', '#forgot-password', sendForgotPassword);

$(document).ready(function(){
    const url = new URL(window.location.href);
    const restoreEmail = url.searchParams.get('restore-email');
    const restoreCode = url.searchParams.get('restore-code');
    if(restoreEmail != null && restoreCode != null){
        $('#login-identification').val(restoreEmail);
        $('#login-password').val(restoreCode);
    }
});
