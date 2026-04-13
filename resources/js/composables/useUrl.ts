/**
 * SSR-safe URL helpers — returns hardcoded paths instead of relying on Ziggy.
 * Use this in template :href bindings to avoid Ziggy crashes during SSR.
 */
export const urls = {
    home: () => '/',
    login: () => '/login',
    register: () => '/signup',
    forgotPassword: () => '/forgot-password',
    logout: () => '/logout',

    // Recipes
    recipes: () => '/recipes',
    recipeShow: (slug: string) => `/recipe/${slug}`,
    recipeCreate: () => '/create',

    // Meal plans
    mealPlans: () => '/meal-plans',
    mealPlansCreate: () => '/meal-plans/create',
    mealPlansShow: (uuid: string) => `/meal-plans/${uuid}`,
    mealPlansSwap: (uuid: string) => `/meal-plans/${uuid}/swap`,

    // Tokens
    tokens: () => '/tokens',

    // Account / settings
    account: (username: string) => `/${username}/account`,
    profile: () => '/settings/profile',
    password: () => '/settings/password',
    verificationSend: () => '/email/verification-notification',
};
