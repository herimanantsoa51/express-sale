import api from './api';


const companyInfoService = {
    /**
     * Récupère les informations de l'entreprise
     * @returns {Promise<Object>} Informations de l'entreprise
     * {
        "name": "Express Salea",
        "phone": "0320001192a",
        "address": "CASIN MALL Behoririkaa",
        "email": "express_sale@gmail.coma",
        "logo_path": null
        }      
    */
    index: async () => {
        const response = await api.get('/company-info');
        console.log('Company info fetched:', response.data);
        return response.data;
    },
    update:async (data)=> {
        console.log('Updating company info with data:', data);
        const response= await api.put('/company-info',data);
        return response.data;
    }
}
export default companyInfoService;