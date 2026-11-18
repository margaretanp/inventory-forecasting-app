import axios from 'axios'

const api = axios.create({
    baseURL: 'http://127.0.0.1:8000/api/inventory',
    headers: {
        Accept: 'application/json',
    },
})

export default {
    getDashboard(alpha = 0.3, recalculate = false) {
        return api.get('/dashboard', {
            params: { alpha, recalculate: recalculate ? 1 : 0 },
        })
    },
    getProductForecast(productId, alpha = 0.3) {
        return api.get(`/products/${productId}/forecast`, {
            params: { alpha },
        })
    },
    recordMovement(payload) {
        return api.post('/movements', payload)
    },
    importCsv(file, replaceExisting = true) {
        const formData = new FormData()
        formData.append('file', file)
        formData.append('replace_existing', replaceExisting ? '1' : '0')
        return api.post('/import', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })
    },
}