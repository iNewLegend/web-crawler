import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';

import { AppComponent } from './app.component';
import { ApiModule } from "../api/api.module";
import { CrawlerComponent } from './crawler/crawler.component';
import { CrawlerControlComponent } from './crawler/crawler-control/crawler-control.component';
import { FormsModule, ReactiveFormsModule } from "@angular/forms";
import { CrawlerDisplayComponent } from './crawler/crawler-display/crawler-display.component';
import { CrawlerDisplayIndexComponent } from "./crawler/crawler-display-index/crawler-display-index.component";

@NgModule( {
    declarations: [
        AppComponent,
        CrawlerComponent,
        CrawlerControlComponent,
        CrawlerDisplayIndexComponent,
        CrawlerDisplayComponent
    ],
    imports: [
        BrowserModule,
        ApiModule,
        ReactiveFormsModule,
        FormsModule,
    ],
    providers: [],
    bootstrap: [ AppComponent ]
} )
export class AppModule {
}
