<x-mail::message>
# Welcome to Sri Ayu Residency!

Hello {{ $resident->name }},

Your resident account has been successfully registered by the system administrator. 

To activate your account and set up your login, please verify your email address by clicking the button below:

<x-mail::button :url="$verificationUrl" color="success">
Verify Resident Account
</x-mail::button>

Once verified, you will be able to log in to the Resident Portal to manage visitors, auto-approve deliveries, and monitor gate logs.

If you did not request this account, please ignore this email.

Thanks,<br>
Sri Ayu Residency Management
</x-mail::message>
