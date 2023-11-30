// import axios from "axios";
import api from "../../utils/api";
import { authActions } from '../auth-slice';
import { uiActions } from "../ui-slice";

export const login = (payload) => {
    return async (dispatch) => {
        dispatch(uiActions.loginLoading());
        
        try {
            // Gửi yêu cầu để nhận CSRF token
            await api.get('/sanctum/csrf-cookie');
            
            // Thực hiện yêu cầu đăng nhập
            const response = await api.post("/api/login", payload);
            const user = response.data;

            // Kiểm tra nếu có lỗi trong dữ liệu nhận được từ server
            if (user.hasOwnProperty('message')) {
                // Có lỗi, dispatch action để xử lý lỗi
                dispatch(uiActions.loginError(user.message));
            } else {
                // Không có lỗi, dispatch action để lưu thông tin người dùng đã đăng nhập
                await dispatch(authActions.login(user));
            }
        } catch (error) {
            console.log(error);
            // Xảy ra lỗi trong quá trình xử lý yêu cầu
            dispatch(uiActions.loginError("An error occurred while processing your request."));
        } finally {
            // Tắt trạng thái loading dù có lỗi hay không
            dispatch(uiActions.loginLoading());
        }
    };
};


export const register = (payload) => {
    return async dispatch => {
        dispatch(uiActions.registerLoading())
        await api.get('/sanctum/csrf-cookie');

        const postData = async () => {
            const response = await api.post("/api/register", payload);

            const data = await response.data;
            return data;
        };

        try {
            const user = await postData();
            await dispatch(authActions.register(user));
            dispatch(uiActions.registerLoading());
        } catch (error) {
            console.log(error);
        }
    }
};


export const logout = (token) => {
    return async dispatch => {
        await api.get('/sanctum/csrf-cookie');

        const logout = async () => {
            const response = await api.post('/api/logout', null, {
                headers: {
                    Authorization: 'Bearer ' + token
                },
                // withCredentials: true
            });
            const message = response.data;
            return message;
        };

        try {
            await logout();
            dispatch(authActions.logout());
            
        } catch (error) {
            console.log(error);
        }

    }
};
