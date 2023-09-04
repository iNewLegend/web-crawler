import { Component, EventEmitter, Input, Output } from '@angular/core';
import { CrawlerInterface, CrawlerInterfaceWithChildCountable, CrawlerInterfaceWithChildren } from "../crawler.model";
import { ApiCrawlerService } from "../../../api/api.crawler.service";

@Component( {
    selector: 'crawler-display-index',
    templateUrl: './crawler-display-index.component.html',
    styleUrls: [ './crawler-display-index.component.scss' ]
} )

export class CrawlerDisplayIndexComponent {
    @Output() updateItemEvent = new EventEmitter<CrawlerInterfaceWithChildren>();

    @Output() deleteItemEvent = new EventEmitter<CrawlerInterface>();

    @Input() items: CrawlerInterfaceWithChildCountable[] = [];

    constructor( private crawler: ApiCrawlerService ) {
    }

    onClick( item: CrawlerInterfaceWithChildCountable ) {
        location.hash = item.id;
    }

    onDeleteClick( item: CrawlerInterfaceWithChildCountable ) {
        this.crawler.deleteCrawler( item.id ).subscribe(
            () => this.deleteItemEvent.emit( item )
        )
    }

    onReloadClick( item: CrawlerInterfaceWithChildCountable ) {
        this.crawler.updateCrawler( item.id ).subscribe( ( item ) =>
            this.updateItemEvent.emit( item )
        );
    }
}
