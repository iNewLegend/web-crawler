import { Observable } from 'rxjs';

import { Injectable } from '@angular/core';

import { HttpClient } from '@angular/common/http';

import {
    CrawlerInterfaceWithChildCountable,
    CrawlerInterfaceWithChildren
} from "../app/crawler/crawler.model";

@Injectable()

export class ApiCrawlerService {

    constructor( private http: HttpClient ) {
    }

    getIndexCrawlers(): Observable<CrawlerInterfaceWithChildCountable[]> {
        return this.http.get( '/crawler' ) as Observable<CrawlerInterfaceWithChildCountable[]>;
    }

    getCrawler( id: string ): Observable<CrawlerInterfaceWithChildren> {
        return this.http.get( '/crawler/' + id ) as Observable<CrawlerInterfaceWithChildren>;
    }

    getCrawlerByUrl( url: string ): Observable<CrawlerInterfaceWithChildren> {
        return this.http.get( '/crawler/?url=' + url ) as Observable<CrawlerInterfaceWithChildren>;
    }

    getCrawlerByHash( hash: string ): Observable<CrawlerInterfaceWithChildren> {
        return this.http.get( '/crawler/?hash=' + hash ) as Observable<CrawlerInterfaceWithChildren>;
    }

    createCrawler( url: string, depth = 0 ): Observable<CrawlerInterfaceWithChildren> {
        return this.http.post( '/crawler', { url, depth }, {} ) as Observable<CrawlerInterfaceWithChildren>;
    }

    updateCrawler( id: string, depth = 0 ): Observable<CrawlerInterfaceWithChildren> {
        return this.http.put( '/crawler/' + id + "?depth=" + depth, {} ) as Observable<CrawlerInterfaceWithChildren>;
    }

    deleteCrawler( id: string ): Observable<CrawlerInterfaceWithChildren> {
        return this.http.delete( '/crawler/' + id ) as Observable<CrawlerInterfaceWithChildren>;
    }
}
