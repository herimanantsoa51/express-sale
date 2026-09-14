import api from './api';
/**
 *  Route::prefix('print')->group(function () {
        Route::get('/test', [PrintController::class, 'testPrinter']);
        Route::get('/sale/{id}', [PrintController::class, 'printSale']);
        Route::get('/credit/{credit}',[PrintController::class, 'printCredit']);
        Route::get('/reservation/{reservation}',[PrintController::class, 'printReservation']);
        Route::get('/reservation-receipt/{reservation}',[PrintController::class, 'printReservationReceipt']);
        Route::get('/cash-count/{cashCount}', [PrintController::class, 'printCashCount']);
        Route::get('/installment-transaction/{installmentTransaction}', [PrintController::class, 'printInstallmentTransaction']);
        
    });
 */

const printService = {
    testPrinter: async () => {
        const response = await api.get('/print/test');
        return response.data;
    },
    printSale: async (id) => {
        const response = await api.get(`/print/sale/${id}`);
        return response.data;
    },
    printCredit: async (creditId) => {
        const response = await api.get(`/print/credit/${creditId}`);
        return response.data;
    },
    printReservation: async (reservationId) => {
        const response = await api.get(`/print/reservation/${reservationId}`);
        return response.data;
    },
    printReservationReceipt: async (reservationId) => {
        const response = await api.get(`/print/reservation-receipt/${reservationId}`);
        return response.data;
    },
    printCashCount: async (cashCountId) => {
        const response = await api.get(`/print/cash-count/${cashCountId}`);
        return response.data;
    },
    printInstallmentTransaction: async (installmentTransactionId) => {
        const response = await api.get(`/print/installment-transaction/${installmentTransactionId}`);
        return response.data;
    }
}

export default printService;