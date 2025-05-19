@php
    $currentUser = auth()->user();
    $isAdmin = $currentUser->role === 'admin';
    $resetUserId = null;
@endphp

<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-lg bg-green-100 border border-green-400 text-green-800 px-4 py-3">
                    {{ session('success') }}
                </div>
            @endif
            <!-- Page Header -->
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-semibold text-gray-900">Users Management</h2>
                    @if($isAdmin)
                    <button type="button" onclick="document.getElementById('addUserModal').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add User
                    </button>
                    @endif
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($users as $user)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800' }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="flex space-x-3">
                                    @if($isAdmin && $currentUser->id === $user->id)
                                    <button type="button" onclick="document.getElementById('changeCredentialsModal').classList.remove('hidden')" class="text-blue-600 hover:text-blue-900">Change Credentials</button>
                                    @endif
                                    
                                    @if($isAdmin && $user->role !== 'admin')
                                    <button type="button" onclick="openResetPasswordModal('{{ $user->id }}', '{{ $user->name }}')" class="text-yellow-600 hover:text-yellow-900">Reset Password</button>
                                    <button type="button" onclick="deleteUser('{{ $user->id }}')" class="text-red-600 hover:text-red-900">Delete</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl px-8 pt-6 pb-6 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('momo.users.store') }}" method="POST">
                    @csrf
                    <div>
                        <h3 class="text-2xl font-semibold text-gray-900 mb-6" id="modal-title">Add New User</h3>
                        <div class="space-y-5">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                                <input type="text" name="name" id="name" required class="block w-full px-4 py-2 rounded-lg border border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" />
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" name="email" id="email" required class="block w-full px-4 py-2 rounded-lg border border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" />
                            </div>
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                                <div class="flex rounded-lg shadow-sm">
                                    <input type="text" name="password" id="password" readonly required class="flex-1 block w-full px-4 py-2 rounded-l-lg border border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" />
                                    <button type="button" onclick="generatePassword()" class="inline-flex items-center px-5 py-2 border border-l-0 border-blue-300 rounded-r-lg bg-gray-50 text-gray-500 font-medium hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out">Generate</button>
                                </div>
                            </div>
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                                <select name="role" id="role" class="block w-full px-4 py-2 rounded-lg border border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                                    <option value="user">User</option>
                                    <option value="status_updater">Status Updater</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 flex justify-between gap-4">
                        <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="w-1/2 py-3 px-4 bg-white border border-blue-300 text-gray-700 font-semibold rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out">Cancel</button>
                        <button type="submit" class="w-1/2 py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Credentials Modal -->
    <div id="changeCredentialsModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form action="{{ route('momo.users.update-credentials') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Change Admin Credentials</h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                <input type="password" name="current_password" id="current_password" required class="block w-full px-4 py-3 rounded-lg border border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" />
                            </div>
                            <div>
                                <label for="new_email" class="block text-sm font-medium text-gray-700">New Email</label>
                                <input type="email" name="new_email" id="new_email" value="{{ $currentUser->email }}" required class="block w-full px-4 py-3 rounded-lg border border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" />
                            </div>
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                                <input type="password" name="new_password" id="new_password" required class="block w-full px-4 py-3 rounded-lg border border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" />
                                <p class="mt-1 text-sm text-gray-500">Password must contain at least 8 characters, including uppercase, lowercase, numbers, and special characters.</p>
                            </div>
                            <div>
                                <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                <input type="password" name="new_password_confirmation" id="new_password_confirmation" required class="block w-full px-4 py-3 rounded-lg border border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" />
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" name="logout" id="logout" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="logout" class="ml-2 block text-sm text-gray-900">Logout after updating credentials</label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:col-start-2 sm:text-sm">Update Credentials</button>
                        <button type="button" onclick="document.getElementById('changeCredentialsModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:col-start-1 sm:text-sm">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl px-8 pt-6 pb-6 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="resetPasswordForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div>
                        <h3 class="text-2xl font-semibold text-gray-900 mb-6" id="modal-title">Reset Password</h3>
                        <p class="mb-4 text-gray-700">Are you sure you want to reset the password for <span id="resetUserName" class="font-bold"></span>?</p>
                        <input type="hidden" name="user_id" id="resetUserId" />
                    </div>
                    <div class="mt-8 flex justify-between gap-4">
                        <button type="button" onclick="closeResetPasswordModal()" class="w-1/2 py-3 px-4 bg-white border border-blue-300 text-gray-700 font-semibold rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out">Cancel</button>
                        <button type="submit" class="w-1/2 py-3 px-4 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function generatePassword() {
            const length = 12;
            const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
            let password = "";
            for (let i = 0; i < length; i++) {
                password += charset.charAt(Math.floor(Math.random() * charset.length));
            }
            document.getElementById('password').value = password;
        }

        function resetPassword(userId) {
            if (confirm('Are you sure you want to reset this user\'s password?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `{{ route('momo.users.reset-password', '') }}/${userId}`;
                form.innerHTML = `@csrf @method('PUT')`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `{{ route('momo.users.destroy', '') }}/${userId}`;
                form.innerHTML = `@csrf @method('DELETE')`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function openResetPasswordModal(userId, userName) {
            document.getElementById('resetPasswordModal').classList.remove('hidden');
            document.getElementById('resetUserId').value = userId;
            document.getElementById('resetUserName').textContent = userName;
            const baseAction = `{{ route('momo.users.reset-password', ['user' => 'USER_ID_PLACEHOLDER']) }}`;
            document.getElementById('resetPasswordForm').action = baseAction.replace('USER_ID_PLACEHOLDER', userId);
        }
        function closeResetPasswordModal() {
            document.getElementById('resetPasswordModal').classList.add('hidden');
        }
    </script>
    @endpush
</x-app-layout> 