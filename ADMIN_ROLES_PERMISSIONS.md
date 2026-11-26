# Admin roles and permissions

Roles are stored in `admin_roles`; each role has a JSON list of named permissions. Backend actions check permissions and assignment scope. The React navigation also hides sections the current role cannot access, but this is not the authorization boundary.

| Role | Intended access |
| --- | --- |
| Super Admin | Full permission set; required to use MFA; can manage privileged staff |
| Admin | Routine operations, staff administration, content/product permissions; cannot assign Admin or Super Admin roles |
| Sales Manager | Enquiries, assignment, follow-ups, tasks, applications, and reports |
| DSA Agent | Assigned enquiries and assigned follow-ups/tasks only |
| Customer Support | Assigned enquiry support and callbacks |
| Content Manager | Content, news, and video permissions; no enquiry listing or customer metrics |
| Read-only Viewer | Read-only enquiry and report permissions |

The current dashboard and server routes apply these permissions. Fine-grained roles are defined centrally in `app/Services/Admin/DefaultAdminRoles.php`. The `crm:sync-admin-roles` command synchronizes built-in role permissions; custom roles remain unchanged. Do not edit role permission JSON directly without auditing the effect on all users assigned to that role.

Admins cannot promote themselves, grant Admin/Super Admin to another account, or deactivate/demote the last active Super Admin. Sales Managers can be assigned as team managers for DSA/support accounts; record scope is limited to the manager, active team, and unassigned records.
