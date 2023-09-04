import { Component, OnInit } from '@angular/core';

import {
    CrawlerDisplayState,
    CrawlerInterface,
    CrawlerInterfaceWithChildCountable,
    CrawlerInterfaceWithChildren
} from "./crawler.model";

import { ApiCrawlerService } from "../../api/api.crawler.service";

import { environment } from "../../environments/environment";

@Component( {
    selector: 'crawler',
    templateUrl: './crawler.component.html',
    styleUrls: [ './crawler.component.scss' ]
} )

export class CrawlerComponent implements OnInit {

    crawlers: CrawlerInterfaceWithChildCountable[] = [];

    selectedCrawler: CrawlerInterfaceWithChildren | null = null;

    shouldDisableCrawlerControl = true;

    displayState: CrawlerDisplayState = CrawlerDisplayState.DisplayLoading;

    constructor( private crawler: ApiCrawlerService ) {
    }

    async ngOnInit() {
        this.crawler.getIndexCrawlers().subscribe( ( data ) => {
            const crawlers = data as CrawlerInterfaceWithChildCountable[],
                id = this.getHash();

            if ( id ) {
                this.selectCrawler( id, crawlers );

                return;
            }

            this.displayState = CrawlerDisplayState.DisplayIndexes;

            this.shouldDisableCrawlerControl = false;

            this.crawlers = crawlers;
        } );

        // On hash change.
        window.addEventListener( 'hashchange', () => {
            this.selectCrawler( this.getHash() );
        } );
    }

    newItemEvent( crawler: CrawlerInterfaceWithChildren ) {
        this.crawlers.push( {
            id: crawler.id,
            url: crawler.url,
            urlHash: crawler.urlHash,
            text: crawler.text,
            depth: crawler.depth,
            ownerIds: crawler.ownerIds,

            createdAt: crawler.createdAt,
            updatedAt: crawler.updatedAt,

            childrenCount: crawler.children?.length || 0
        } );
    }

    updateItemEvent( crawler: CrawlerInterfaceWithChildren ) {
        const index = this.crawlers.findIndex( ( i ) => i.id === crawler.id );

        if ( index === -1 ) {
            return this.newItemEvent( crawler );
        }

        this.crawlers[ index ] = {
            id: crawler.id,
            url: crawler.url,
            urlHash: crawler.urlHash,
            text: crawler.text,
            depth: crawler.depth,
            ownerIds: crawler.ownerIds,

            createdAt: crawler.createdAt,
            updatedAt: crawler.updatedAt,

            childrenCount: crawler.children?.length || 0
        };
    }

    deleteItemEvent( crawler: CrawlerInterface ) {
        const index = this.crawlers.findIndex( ( i ) => i.id === crawler.id );

        if ( index === -1 ) {
            return;
        }

        this.crawlers.splice( index, 1 );
    }

    private async selectCrawler( id: string, crawlers = this.crawlers ) {
        // Set loading state.
        this.displayState = CrawlerDisplayState.DisplayLoading;

        const crawler = crawlers.find( ( i ) => i.id === id );

        if ( crawler ) {
            // Fallback.
            this.selectedCrawler = {
                ... crawler,
                children: []
            } as CrawlerInterfaceWithChildren;

            this.crawler.getCrawler( this.selectedCrawler.id ).subscribe(
                this.setSpecificCrawler.bind( this )
            );

            return;
        }

        if ( id.length === environment.hashUrlLength ) {
            // Get crawler by hash.
            this.crawler.getCrawlerByHash( id ).subscribe( ( data ) => {
                this.selectedCrawler = data as CrawlerInterfaceWithChildren;

                this.crawler.updateCrawler( this.selectedCrawler.id ).subscribe( ( data ) => {
                    this.setSpecificCrawler( data );
                } );
            } );

            return;
        }

        this.displayState = CrawlerDisplayState.DisplayIndexes;

        this.selectedCrawler = null;
    }

    private setSpecificCrawler( crawler: CrawlerInterfaceWithChildren ) {
        this.selectedCrawler = crawler;

        this.displayState = CrawlerDisplayState.DisplayLinks;

        this.shouldDisableCrawlerControl = true;
    }

    private getHash() {
        return window.location.hash.substring( 1 );
    }
}
