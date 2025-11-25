<?php
/**
 * User Management Page
 * Blog Advanced v2.0
 */

session_start();

require_once __DIR__ . '/../app/classes/Database.php';
require_once __DIR__ . '/../app/classes/User.php';

// Check if user is logged in and has admin permission
if (!User::isLoggedIn() || !User::hasPermission(User::ROLE_ADMIN)) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$userManager = new User($db);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $result = $userManager->create([
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? '',
                'role' => $_POST['role'] ?? User::ROLE_VIEWER,
                'display_name' => $_POST['display_name'] ?? '',
                'status' => $_POST['status'] ?? User::STATUS_ACTIVE
            ]);
            echo json_encode($result);
            exit;
            
        case 'update':
            $userId = $_POST['user_id'] ?? 0;
            $result = $userManager->update($userId, [
                'email' => $_POST['email'] ?? null,
                'role' => $_POST['role'] ?? null,
                'display_name' => $_POST['display_name'] ?? null,
                'status' => $_POST['status'] ?? null,
                'password' => !empty($_POST['password']) ? $_POST['password'] : null
            ]);
            echo json_encode($result);
            exit;
            
        case 'delete':
            $userId = $_POST['user_id'] ?? 0;
            $result = $userManager->delete($userId);
            echo json_encode($result);
            exit;
    }
}

// Get all users
$users = $userManager->getAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Blog Advanced</title>
    <link rel="stylesheet" href="../static/css/admin.css">
    <style>
        .users-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .btn-primary {
            background: #1877f2;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            background: #166fe5;
        }
        
        .users-table {
            width: 100%;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .users-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th,
        .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e4e6eb;
        }
        
        .users-table th {
            background: #f0f2f5;
            font-weight: 600;
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .role-super_admin { background: #dc3545; color: white; }
        .role-admin { background: #fd7e14; color: white; }
        .role-editor { background: #0dcaf0; color: white; }
        .role-viewer { background: #6c757d; color: white; }
        
        .status-active { color: #28a745; }
        .status-inactive { color: #6c757d; }
        .status-banned { color: #dc3545; }
        
        .btn-action {
            padding: 5px 10px;
            margin: 0 2px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-edit { background: #0dcaf0; color: white; }
        .btn-delete { background: #dc3545; color: white; }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal.active {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="users-container">
        <div class="users-header">
            <h1>User Management</h1>
            <button class="btn-primary" onclick="openCreateModal()">+ Add New User</button>
        </div>
        
        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Display Name</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['display_name']) ?></td>
                        <td>
                            <span class="role-badge role-<?= $user['role'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $user['role'])) ?>
                            </span>
                        </td>
                        <td class="status-<?= $user['status'] ?>">
                            <?= ucfirst($user['status']) ?>
                        </td>
                        <td><?= $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never' ?></td>
                        <td>
                            <button class="btn-action btn-edit" onclick='editUser(<?= json_encode($user) ?>)'>Edit</button>
                            <?php if ($user['role'] !== 'super_admin'): ?>
                            <button class="btn-action btn-delete" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">Delete</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Create/Edit User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Add New User</h2>
            <form id="userForm">
                <input type="hidden" id="userId" name="user_id">
                <input type="hidden" id="formAction" name="action" value="create">
                
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" id="username" required>
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" id="email" required>
                </div>
                
                <div class="form-group">
                    <label>Display Name</label>
                    <input type="text" name="display_name" id="display_name">
                </div>
                
                <div class="form-group">
                    <label>Password <span id="passwordHint">*</span></label>
                    <input type="password" name="password" id="password">
                </div>
                
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" id="role" required>
                        <option value="viewer">Viewer</option>
                        <option value="editor">Editor</option>
                        <option value="admin">Admin</option>
                        <?php if (User::getCurrentUserRole() === User::ROLE_SUPER_ADMIN): ?>
                        <option value="super_admin">Super Admin</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" id="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="banned">Banned</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn-primary">Save</button>
                    <button type="button" class="btn-primary" onclick="closeModal()" style="background: #6c757d;">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Add New User';
            document.getElementById('formAction').value = 'create';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('username').disabled = false;
            document.getElementById('password').required = true;
            document.getElementById('passwordHint').textContent = '*';
            document.getElementById('userModal').classList.add('active');
        }
        
        function editUser(user) {
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('formAction').value = 'update';
            document.getElementById('userId').value = user.id;
            document.getElementById('username').value = user.username;
            document.getElementById('username').disabled = true;
            document.getElementById('email').value = user.email;
            document.getElementById('display_name').value = user.display_name || '';
            document.getElementById('role').value = user.role;
            document.getElementById('status').value = user.status;
            document.getElementById('password').required = false;
            document.getElementById('password').value = '';
            document.getElementById('passwordHint').textContent = '(leave empty to keep current)';
            document.getElementById('userModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('userModal').classList.remove('active');
        }
        
        function deleteUser(userId, username) {
            if (!confirm(`Are you sure you want to delete user "${username}"?`)) {
                return;
            }
            
            fetch('users.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=delete&user_id=${userId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('User deleted successfully');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
        
        document.getElementById('userForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('users.php', {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('User saved successfully');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });
    </script>
</body>
</html>
