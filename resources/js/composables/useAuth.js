import { ref, computed } from 'vue';
import { api } from '../services/api';

const user = ref(null);
const permissions = ref([]);
const auditType = ref(null);
const loading = ref(true);
const authError = ref(null);

export function useAuth() {
    const isAuthenticated = computed(() => !!user.value);
    const roleName = computed(() => user.value?.role?.name || '');
    const roleDisplayName = computed(() => user.value?.role?.display_name || '');
    const isAdmin = computed(() => roleName.value === 'admin');
    const isManager = computed(() => roleName.value === 'manager');
    const isAsstManager = computed(() => ['asst_treasury_manager', 'asst_cashier_manager'].includes(roleName.value));
    const isSupervisor = computed(() => ['area_treasury_supervisor', 'area_cashier_supervisor'].includes(roleName.value));

    /**
     * Check if current user has a specific permission.
     */
    function hasPermission(permissionName) {
        if (!permissions.value || !permissions.value.length) {
            return false;
        }
        return permissions.value.includes(permissionName);
    }

    /**
     * Check if current user has any of the given roles.
     */
    function hasRole(roles) {
        if (!roleName.value) return false;
        if (Array.isArray(roles)) {
            return roles.includes(roleName.value);
        }
        return roleName.value === roles;
    }

    /**
     * Check if user has access to a specific audit type (treasury or cashier).
     */
    function canAccessAuditType(type) {
        if (isAdmin.value || isManager.value) return true;
        return auditType.value === type;
    }

    /**
     * Fetch the currently authenticated user and permissions from session.
     */
    async function checkAuth() {
        loading.value = true;
        authError.value = null;
        try {
            const data = await api.get('/api/auth/me');
            user.value = data.user;
            permissions.value = data.permissions || [];
            auditType.value = data.audit_type || null;
        } catch {
            user.value = null;
            permissions.value = [];
            auditType.value = null;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Log in user with credentials.
     */
    async function login(credentials) {
        authError.value = null;
        const data = await api.post('/api/auth/login', credentials);
        user.value = data.user;
        permissions.value = data.permissions || [];
        auditType.value = data.audit_type || null;
        return data;
    }

    /**
     * Register a new user.
     */
    async function register(userData) {
        authError.value = null;
        return await api.post('/api/auth/register', userData);
    }

    /**
     * Log out current user session.
     */
    async function logout() {
        try {
            await api.post('/api/auth/logout');
        } finally {
            user.value = null;
            permissions.value = [];
            auditType.value = null;
        }
    }

    return {
        user,
        permissions,
        auditType,
        loading,
        authError,
        isAuthenticated,
        roleName,
        roleDisplayName,
        isAdmin,
        isManager,
        isAsstManager,
        isSupervisor,
        hasPermission,
        hasRole,
        canAccessAuditType,
        checkAuth,
        login,
        register,
        logout,
    };
}
