# Step up authentication for Nextcloud	🚀 🔒 

The Step Up Authentication app allows you to trigger a second factor authentication for Single Sign On (SSO)  accounts.

Accounts created in Nextcloud with a SSO provider (saml, ldap, global site selector) can not currently use native Nextcloud Multi Factor Authentication (MFA). Nextcloud expects an Identity Provider (IdP) to do MFA prior to logging in to Nextcloud. This is confusing to users who can still see the settings for MFA in their security settings and it is not allways feasable for IdPs to implement MFA. Instead this app allows administrators to enable users to do step up authentication in Nextcloud, that is, provide a higher level of assurance of identity within Nextcloud, than what is provided by the IdP.

The only thing that this app does is to provide the same behaviour for SSO accounts as for local accounts. That means that you can configure groups in which users are required to have (or be excluded from having) MFA configured for their account. Users who are required to have MFA enabled, but have not yet configured a provider, will be prompted upon first login after requirement is enabled. The users can also voluntarily enable and disable MFA providers on their own volition if no external requirements are enforced. The documentation for MFA in Nextcloud is available here:

* https://docs.nextcloud.com/server/latest/admin_manual/configuration_user/two_factor-auth.html

## Testing status
These uses-cases has been manually tested with 0.2.0 version of the app
| **Use-case**                                                                     | **Expected Environments**       | **Description**                                                                                                  | **Expected Behaviour**                                                                                                                                                                                                    | **Actual Behaviour (OS/Client combo tested)**|
|----------------------------------------------------------------------------------|---------------------------------|------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------|
| Web logon without MFA provider configured, no MFA required group membership      | All*                            | User has no configuration options set that would trigger an MFA flow                                             | User is logged in without any questions about MFA                                                                                                                                                                         | As expected (8,20)                            |
| Web logon without MFA provider configured, MFA required group membership present | "                               | User belongs to a group that forces MFA                                                                          | User has to configure a second factor on first login.                                                                                                                                                                     | As expected (8)                               |
| Web logon with TOTP provider configured                                          | "                               | User has configured a time based one time password.                                                              | User is prompted for one time password on login.                                                                                                                                                                          | As expected (8,16)                            |
| Web logon with Webauthn provider configured                                      | "                               | User has configured a Webauthn provider, such as a yubikey.                                                      | User is prompted for the&nbsp; Webauthn device.                                                                                                                                                                           | As expected (8)                               |
| Web logon with multiple Webauthn providers configured                            | "                               | User has configured multiple Webauthn providers, such as multiple yubikeys.                                      | User is prompted for the&nbsp; Webauthn device.                                                                                                                                                                           | As expected (8)                               |
| Web logon with TOTP and Webauthn provider configured                             | "                               | User has configured both time based one time password and a Webauthn provider, such as yubikey.                  | User is asked to select which second factor to use, and is subsequently prompted for that factor,                                                                                                                         | As expected (8)                               |
| Web logon with TOTP and multiple Webauthn providers configured                   | "                               | User has configured both time based one time password and multiple Webauthn provider, such as multiple yubikeys. | User is asked to select which second factor to use, and is subsequently prompted for that factor,                                                                                                                         | As expected (8)                               |
| Admin twofactorauth:disable                                                      | "                               | The administrator disables one of several of the MFA providers for a user                                        | This triggers the use case corresponding the the remaining configured providers, upon next login.                                                                                                                         | As expected (8)                               |
| Admin twofactorauth:disable                                                      | "                               | The administrator disables all of the MFA providers for a user                                                   | This triggers the use case "Web logon without MFA provider configured, no MFA required group membership" for the user upon next login.                                                                                    | As expected (8)                               |
| Admin twofactorauth:enforce                                                      | "                               | The administrator enforces MFA for a user without MFA                                                            | This triggers the use case "Web logon without MFA provider configured,&nbsp; MFA required group membership present" upon next login.                                                                                      | As expected (8)                               |
| Desktop client logon - Single sign on with application token                     | Windows/Linux/Mac/(Mobile)      | The user has a single sign on account, and logs in using an app token, created in the security settings.         | The user is allowed in, with only the app token, no second factor is ever prompted for.                                                                                                                                   | As expected (9)                               |
| Desktop client logon - Single sign on, normal case (not using app token)         | "                               | The user has a single sign on account, and logs in using the IdP.                                                | Depending on configuration of the user account, the same flow as the browser case is triggered, except that Webauthn devices are not supported by the webview, and will not be concided configured by the desktop client. | As expected (3,9,18)                          |
| WebDAV access                                                                    | Windows/Linux/Mac/Python/Rclone | The user has a single sign on account, and logs in using an app token, created in the security settings.         | The user is allowed in, with only the app token, no second factor is ever prompted for.                                                                                                                                   | As expected (10)                              |
| Receiving file local file shares                                                 | Browser/Desktop client          | The user receives a file share from someone on the same node.                                                    | The file is immediately synced (depending on file size settings in desktop client, which may modify this behavior)                                                                                                        | As expected (8)                               |
| Receiving federated file shares                                                  | "                               | The user receives a file share from someone on another node.                                                     | The file is synced upon accept of share (depending on file size settings in desktop client, which may modify this behavior)                                                                                               | As expected (8,16,18)                         |
| Sending local file shares                                                        | "                               | The user shares a file with another user on the same node.                                                       | The share is immediately accepted by the user.                                                                                                                                                                            | As expected (8)                               |
| Sending federated file shares                                                    | "                               | The user shares a file with someone on another node.                                                             | The remote user is notified of the share, and can except if deemed appropriate.                                                                                                                                           | As expected (8,16,18)                         |
| File access in home folder                                                       | Browser/Desktop client          | The user access a file in the root of the file system (primary storage)                                          | The user can create, delete and modify files if quota is sufficient.                                                                                                                                                      | As expected (8)                               |
| File access in personal S3-buckets                                               | Browser/Desktop client          | The user access a file in an external s3 mount, only visible to them.                                            | The user can create, delete and modify files in all cases.                                                                                                                                                                | As expected (8)                               |
| File access in system S3-buckets                                                 | Browser/Desktop client          | The user access a file in an external s3 mount,&nbsp; visible to them and their collaborators as well as admins. | The user can create, delete and modify files depending on access rules.                                                                                                                                                   | As expected (8)                               |

*All means:
* OS
  - Android
  - iOS
  - Linux
  - Mac
  - Windows
* Client
  - Chromium based
  - Firefox
  - Nextcloud client
  - Rclone
  - Safari

Combinations:
| **Number** | **OS** | **Client** |
| ---------- | ------ | ---------- |
| 1          | Android| Chromium   |
| 2          | Android| Firefox    |
| 3          | Android| Nextcloud  |
| 4          | Android| Rclone     |
| 5          | iOS    | Nextcloud  |
| 6          | iOS    | Safari     |
| 7          | Linux  | Chromium   |
| 8          | Linux  | Firefox    |
| 9          | Linux  | Nextcloud  |
| 10         | Linux  | Rclone     |
| 11         | Mac    | Chromium   |
| 12         | Mac    | Firefox    |
| 13         | Mac    | Nextcloud  |
| 14         | Mac    | Rclone     |
| 15         | Mac    | Safari     |
| 16         | Windows| Chromium   |
| 17         | Windows| Firefox    |
| 18         | Windows| Nextcloud  |
| 19         | Windows| Rclone     |
| 20         | Windows| Webdav     |

 
Operating systems and client combinations actually tested are mentioned in the column "Actual behaviour", we expect to test more combinations with time, see [#1](https://github.com/SUNET/nextcloud-stepupauth/issues/1).
