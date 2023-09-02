import { NgModule } from '@angular/core';

import { HTTP_INTERCEPTORS, HttpClientModule } from '@angular/common/http';

import { ApiBaseUrlInterceptor } from './api.base-url.interceptor';
import { ApiCrawlerService } from "./api.crawler.service";

@NgModule( {
    declarations: [],
    imports: [ HttpClientModule ],
    providers: [
        {
            provide: HTTP_INTERCEPTORS,
            useClass: ApiBaseUrlInterceptor,
            multi: true,
        },

        ApiCrawlerService,

    ],
    bootstrap: [],
} )

export class ApiModule {
}
