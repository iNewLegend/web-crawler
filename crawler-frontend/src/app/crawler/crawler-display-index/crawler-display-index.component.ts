import { Component, Input } from '@angular/core';
import { CrawlerInterfaceWithChildCountable } from "../crawler.model";
import { ApiCrawlerService } from "../../../api/api.crawler.service";

@Component( {
    selector: 'crawler-display-index',
    templateUrl: './crawler-display-index.component.html',
    styleUrls: [ './crawler-display-index.component.scss' ]
} )

export class CrawlerDisplayIndexComponent {
    @Input() items: CrawlerInterfaceWithChildCountable[] = [];

    constructor( private crawler: ApiCrawlerService ) {
    }

    onClick( item: CrawlerInterfaceWithChildCountable ) {
        location.hash = item.id;
    }

    onDeleteClick( item: CrawlerInterfaceWithChildCountable ) {
        this.crawler.deleteCrawler( item.id ).subscribe( () => {
            // TODO - Lazy, no time.
            location.hash = '';
            location.reload();

            // // Remove ownerIds from items
            // this.items = this.items.map( ( i ) => {
            //     if ( i.ownerIds ) {
            //         i.ownerIds = i.ownerIds.filter( ( id ) => id !== item.id );
            //         i.childrenCount = i.ownerIds.length;
            //     }
            //
            //     return i;
            // } );
            //
            // this.items = this.items.filter( ( i ) => i.id !== item.id );
        } );
    }
}
