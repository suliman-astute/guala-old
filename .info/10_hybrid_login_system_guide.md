# Hybrid Login System Architecture (Local + LDAP)

## 1. System Flow Overview

This authentication system implements a **Hybrid Multi-Factor Logic** that attempts to authenticate users via three distinct methods in a specific priority order. It is designed to support mixed environments where some users are local (external consultants, machine operators) and others are corporate employees (Active Directory).

### The Authentication Waterfall:

1.  **Method A: Local Email Login**
    *   **Trigger**: Input looks like an email address (contains `@`).
    *   **Action**: Authenticates against the local `users` table using `email` and `password`.
    *   **Use Case**: External users or admins without AD accounts.

2.  **Method B: Local Matricola (Badge ID) Login**
    *   **Trigger**: Input is purely numeric (regex `/^\d+$/`).
    *   **Action**: Authenticates against the local `users` table using `matricola` column and `password`.
    *   **Use Case**: Plant operators who log in via touch screens using their employee ID.

3.  **Method C: LDAP / Active Directory Login**
    *   **Trigger**: Input is a username (e.g., `mrossi`) or domain format (`DOMAIN\mrossi`), and previous methods failed or didn't apply.
    *   **Action**:
        1.  **Pre-check**: Verifies if the user exists in the local `users` table AND has the flag `is_ad_user = 1`. If not, login is rejected immediately (preventing LDAP brute force).
        2.  **Domain Resolution**: Determines which AD server to contact.
            *   Priority 1: `users.tipo_dominio` (User-specific domain assignment).
            *   Priority 2: Extracted NetBIOS name from input (e.g., `GUALADIS` from `GUALADIS\mrossi`).
        3.  **Dynamic Connection**: Retrieves server config (Host, Port, Base DN) from the `table_gestione_ad` table and establishes a runtime connection using `LdapRecord`.
        4.  **Bind/Auth**: Attempts to bind to AD using the user's credentials.
        5.  **User Verification**: Checks if the user exists in the AD structure (matching `samaccountname` or `userprincipalname`).
        6.  **Local Session**: If AD accepts the credentials, the system logs in the *local* Laravel user instance.

---

## 2. Database Structure

Two main tables are required to support this functionality.

### A. `users` Table (Extensions)
Standard Laravel users table with custom columns added for this flow.

| Column Name | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt | Primary Key. |
| `name` | String | **Username**. Matches `samaccountname` for AD users. |
| `email` | String | Used for Method A (Email Login). Nullable for operators. |
| `password` | String | Hashed password for Local/Matricola login. (Ignored for AD users). |
| `matricola` | String | **Badge ID**. Used for Method B. Unique identifier for operators. |
| `is_ad_user` | Boolean | **Flag**. `1` = Allow LDAP login. `0` = Local auth only. |
| `tipo_dominio` | String | **Domain Key**. Links to `table_gestione_ad.dominio`. Example: "GUALADIS". |
| `valido` | Integer | (Optional) Account status flag (1=Active). |

### B. `table_gestione_ad` Table
Stores configuration for one or multiple Active Directory servers. Model: `App\Models\ad`.

| Column Name | Type | Example Value | Description |
| :--- | :--- | :--- | :--- |
| `id` | BigInt | 1 | Primary Key. |
| `dominio` | String | `GUALADIS` | NetBIOS name or internal identifier. Matches `users.tipo_dominio`. |
| `host` | String | `192.168.1.10` | IP address or FQDN of the Domain Controller. |
| `base_dn` | String | `DC=guala,DC=local` | Root of the directory tree to search users in. |
| `porta` | Integer | `389` | Standard LDAP port (389) or LDAPS (636). |
| `dominio_dns`| String | `guala.local` | (Optional) Used to construct UPNs (`user@guala.local`). |

---

## 3. Instructions to Convert a Simple Login System

Follow these steps to upgrade a standard Laravel `auth` system to this Hybrid AD/Local system.

### Step 1: Install Dependencies
You need the `LdapRecord` library to handle low-level LDAP connections comfortably.

```bash
composer require directorytree/ldaprecord
```

### Step 2: Create/Modify Database Tables
You need to add the configuration table and extend the users table.

**Run this migration:**
```php
Schema::create('table_gestione_ad', function (Blueprint $table) {
    $table->id();
    $table->string('dominio'); // e.g., COMPANY
    $table->string('host');    // e.g., 10.0.0.1
    $table->string('base_dn'); // e.g., DC=company,DC=com
    $table->integer('porta')->default(389);
    $table->string('dominio_dns')->nullable();
    $table->timestamps();
});

Schema::table('users', function (Blueprint $table) {
    $table->string('matricola')->nullable()->index();
    $table->boolean('is_ad_user')->default(0);
    $table->string('tipo_dominio')->nullable();
    // Ensure 'name' column exists and is used as username
});
```

### Step 3: Create the AD Model
Create `app/Models/ad.php`:

```php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ad extends Model {
    protected $table = 'table_gestione_ad';
    protected $fillable = ['dominio', 'host', 'base_dn', 'porta'];
}
```

### Step 4: Implement the Login Logic
Override the `login` or `attemptLogin` method in your `LoginController`.

1.  **Open** `app/Http/Controllers/Auth/LoginController.php`.
2.  **Import Classes**:
    ```php
    use LdapRecord\Container;
    use LdapRecord\Connection;
    use LdapRecord\Models\ActiveDirectory\User as LdapUser;
    use App\Models\User;
    use App\Models\ad;
    use Illuminate\Validation\ValidationException;
    ```
3.  **Replace `attemptLogin`**:
    Copy the logic from the provided source code. Key implementation details to ensure:
    *   **Input Normalization**: Determine if input is email, number, or string.
    *   **Local Pass**: Check `Auth::attempt` first for non-AD accounts.
    *   **AD Configuration Fetch**: Query `table_gestione_ad` before connecting.
    *   **Runtime Connection**: Use `Container::addConnection` to create a connection on the fly based on DB values (allows multi-tenant AD support without restarting config).
    *   **Fail-safe**: Wrap LDAP calls in `try-catch` to handle network timeouts gracefully.

### Step 5: Configure the Login View
Ensure your login form input is named generically (e.g., `email` or `login`) but accepts all formats. Update the label to reflect this: "Email, Badge ID, or Username".

### Step 6: Testing
1.  **Insert a Test Domain** in `table_gestione_ad`.
2.  **Create a Local User** with `is_ad_user = 1`, `tipo_dominio = 'YOURDOMAIN'`, and `name = 'your.ad.username'`.
3.  **Try Logging in** with your Windows password.
