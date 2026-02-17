import { defineStore } from 'pinia';
import { db } from '../db/pos-db';
import { syncMasterData } from '../services/sync-service';
import { useConnectivityStore } from './connectivity';

export const useProductsStore = defineStore('products', {
    state: () => ({
        products: [],
        categories: [],
        searchQuery: '',
        selectedCategory: null,
        loading: false,
    }),
    
    getters: {
        filteredProducts: (state) => {
            let result = state.products;
            
            // Filter by Category
            if (state.selectedCategory) {
                result = result.filter(p => p.category_id === state.selectedCategory);
            }
            
            // Filter by Search
            if (state.searchQuery) {
                const q = state.searchQuery.toLowerCase();
                result = result.filter(p => 
                    (p.name && p.name.toLowerCase().includes(q)) || 
                    (p.sku && p.sku.toLowerCase().includes(q))
                );
            }
            
            return result;
        },
        
        hasProducts: (state) => state.products.length > 0
    },
    
    actions: {
        async loadFromDb() {
            try {
                // Load products
                this.products = await db.products.where('is_active').equals(true).toArray();
                
                // If index doesn't exist yet (migration might be needed if old DB), fallback
                if (this.products.length === 0) {
                     // Try fetching all
                     const all = await db.products.toArray();
                     this.products = all.filter(p => p.is_active !== false); // Default true
                }
                
                // Load categories
                const allCats = await db.categories.toArray();
                
                // Filter categories to show only those having products
                const usedCategoryIds = new Set(this.products.map(p => p.category_id));
                this.categories = allCats.filter(c => usedCategoryIds.has(c.id));
                
            } catch (error) {
                console.error('Failed to load products from DB:', error);
            }
        },
        
        async syncAndReload() {
            const connectivity = useConnectivityStore();
            this.loading = true;
            
            try {
                if (connectivity.isOnline) {
                    await syncMasterData();
                }
            } catch (error) {
                console.error('Sync failed, falling back to local data:', error);
            } finally {
                // Always load from DB after attempt
                await this.loadFromDb();
                this.loading = false;
            }
        },
        
        setSearchQuery(query) {
            this.searchQuery = query;
        },
        
        selectCategory(id) {
            this.selectedCategory = id;
        }
    }
});
