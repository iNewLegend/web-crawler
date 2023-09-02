import { Injectable } from '@angular/core';

import { HttpHandler, HttpInterceptor, HttpRequest } from '@angular/common/http';

import { environment } from "../environments/environment";

@Injectable()

export class ApiBaseUrlInterceptor implements HttpInterceptor {
    intercept( request: HttpRequest<any>, next: HttpHandler ) {
        const modifiedRequest = request.clone( {
            url: environment.baseUrl + request.url,
        } );

        return next.handle( modifiedRequest );
    }
}
